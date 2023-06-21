<?php

namespace Source\App;

use Source\Core\View;
use Source\Models\Auth;
use Source\Models\Post;
use Source\Models\User;
use Source\Support\Email;
use Source\Support\Upload;
use Source\Core\Controller;
use Source\Core\Session;
use Source\Support\Message;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\CafeApp\AppWallet;
use Source\Models\CafeApp\AppInvoice;
use Source\Models\CafeApp\AppCategory;
use Source\Support\Thumb;

class App extends Controller
{

    private $user;

    ##########################
        /** CONSTRUTOR **/
    ##########################
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_APP . "/");

        if (!$this->user = Auth::user()) {
            $this->message->warning("Efetue login para acessar o APP.")->flash();
            redirect("/entrar");
        }

        (new Access())->report();
        (new Online())->report();

        (new AppWallet())->start($this->user);
        (new AppInvoice())->fixed($this->user, 3);

        //Confirmação de E-Mail!
        if ($this->user->status != "confirmed") {
            $session = new Session();
            if (!$session->has("appconfirmed")) {
                $this->message->info("IMPORTANTE: Acesse seu e-mail para confirmar seu cadastro e ativar todos os recursos.")->flash();
                $session->set("appconfirmed", true);
                (new Auth())->register($this->user);
            }
        }
    }
    ##########################
       /** PAGINA HOME **/
    ##########################
    public function home()
    {
        $head = $this->seo->render(
            "Olá {$this->user->first_name}. Vamos controlar? - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        //CHART
        $dateChart =[];
        for ($month = -4; $month <= 0; $month++) {
            $dateChart[] = date("m/Y", strtotime("{$month}Month"));
        }

        $chartData = new \stdClass();
        $chartData->categories = "'" . implode("','", $dateChart) . "'";
        $chartData->expense = "0,0,0,0,0";
        $chartData->income = "0,0,0,0,0";

        $chart = (new AppInvoice())
        ->find("user_id = :user AND status = :status AND due_at >= DATE(2018-12-31) GROUP BY year(due_at) ASC, month(due_at) ASC", 
        "user={$this->user->id}&status=paid",
        "year(due_at) AS due_year,
         month(due_at) AS due_month,
         DATE_FORMAT(due_at, '%m/%Y') AS due_date,
         (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'expense' AND year(due_at) = due_year AND month(due_at) = due_month) AS expense,
         (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'income' AND year(due_at) = due_year AND month(due_at) = due_month) AS income
        "
        )->limit(5)->fetch(true);
        
        if ($chart) {
            $chartCategories = [];
            $chartExpense = [];
            $chartIncome = [];

            foreach ($chart as $chartItem) {
                $chartCategories[] = $chartItem->due_date;
                $chartExpense[] = ($chartItem->expense ? $chartItem->expense : 0);
                $chartIncome[] = ($chartItem->income ? $chartItem->income : 0);
            }

            $chartData->categories = "'" . implode("','", $chartCategories) . "'";
            $chartData->expense = implode(",", array_map("abs", $chartExpense));
            $chartData->income = implode(",", array_map("abs", $chartIncome));

        }
        //END CHART
        //INCOME && EXPENSE

        $income = (new AppInvoice())
        ->find("user_id = :user AND type = 'income' AND status = 'unpaid' AND date(due_at) <= date(now() + INTERVAL 1 MONTH)",
        "user={$this->user->id}")
        ->order("due_at")
        ->fetch(true);

        $expense = (new AppInvoice())
        ->find("user_id = :user AND type = 'expense' AND status = 'unpaid' AND date(due_at) <= date(now() + INTERVAL 1 MONTH)",
        "user={$this->user->id}")
        ->order("due_at")
        ->fetch(true);

        //END INCOME && EXPENSE
        //WALLET
        $wallet = (new AppInvoice())->find("user_id = :user AND status = :status", "user={$this->user->id}&status=paid",
        "(SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'income')AS income,
        (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'expense')AS expense"
        )->fetch();

       if ($wallet) {
        $wallet->wallet = $wallet->income - $wallet->expense;
       }
        //END WALLET
        //POST
        $post = (new Post())->find()->limit(2)->order("post_at DESC")->fetch(true);
        //END POST

        echo $this->view->render("home", [
            "head" => $head,
            "chart"=> $chartData,
            "income"=> $income,
            "expense"=> $expense,
            "wallet" => $wallet,
            "posts" => $post
        ]);
    }

    public function filter(array $data)
    {
        $status = (!empty($data['status']) ? $data['status'] : "all");
        $category = (!empty($data['category']) ? $data['category'] : "all");
        $date = (!empty($data['date']) ? $data['date'] : date("m/Y"));

        list($m, $y) = explode("/", $date);
        $m = ($m >= 1 && $m <= 12 ? $m : date("m"));
        $y = ($y <= date("Y", strtotime("+10year")) ? $y : date("Y", strtotime("+10year")));

        $start = new \DateTime(date("Y-m-t"));
        $end = new \DateTime(date("Y-m-t", strtotime("{$y}-{$m}+1month")));
        $diff = $start->diff($end);

        if (!$diff->invert) {
            $afterMonths = (floor($diff->days / 30));
            (new AppInvoice())->fixed($this->user, $afterMonths);
        }

        $redirect = ($data['filter'] === "income" ? "receber" : "pagar");
        $json['redirect'] = url("/app/{$redirect}/{$status}/{$category}/{$m}-{$y}");
        echo json_encode($json);

    }

    public function onpaid(array $data): void
    {
        $invoice = (new AppInvoice())
        ->find("user_id = :user AND id = :id", "user={$this->user->id}&id={$data['invoice']}")
        ->fetch();
        
        if (!$invoice) {
            $this->message->error("Ops! ocorreu um erro ao atualizar o lancçamento :/")->flash();
            $json["reload"] = true;
            echo json_encode($json);
            return ;
        }
        $invoice->status = ($invoice->status === "paid" ? "unpaid" : "paid");
        $invoice->save();

        $y = date("Y");
        $m = date("m");
        if ($data["date"]) {
            list($m, $y) = explode("/", $data["date"]);
        }

        $json["onpaid"] = (new AppInvoice())->balance($this->user, $y, $m, $invoice->type);
        echo json_encode($json);
        
    }
    ##########################
      /** PAGINA RECEBER **/
    ##########################
    public function income(?array $data): void
    {   
       
        $head = $this->seo->render(
            "Minhas receitas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        $categories = (new AppCategory())
        ->find("type = :t", "t=income", "id, name")
        ->order("order_by, name")
        ->fetch(true);

        echo $this->view->render("invoices", [
            "user" => $this->user,
            "head" => $head,
            "type" => "income",
            "categories" => $categories,
            "invoices" => (new AppInvoice())->filter($this->user, "income", (!empty($data) ? $data : null)),
            "filter" => (object)[
                "status" => (!empty($data['status']) ? $data['status'] : null),
                "category" => (!empty($data['category']) ? $data['category'] : null),
                "date" => (!empty($data['date']) ? str_replace("-", "/", $data['date']) : null)
             ]
        ]);
    }

    ##########################
       /** PAGINA PAGAR **/
    ##########################
    public function expense(?array $data): void
    {
        $head = $this->seo->render(
            "Minhas despesas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        $categories = (new AppCategory())->find("type = :t", "t=expense", "id, name")->order("order_by, name")->fetch(true);
        
        echo $this->view->render("invoices", [
            "user" => $this->user,
            "head" => $head,
            "type" => "expense",
            "categories" => $categories,
            "invoices" => (new AppInvoice())->filter($this->user, "expense", (!empty($data) ? $data : null)),
            "filter" => (object)[
                "status" => (!empty($data['status']) ? $data['status'] : null),
                "category" => (!empty($data['category']) ? $data['category'] : null),
                "date" => (!empty($data['date']) ? str_replace("-", "/", $data['date']) : null)
             ]
        ]);
    }

    ##########################
       /** PAGINA FIXAS **/
    ##########################
    public function fixed(): void
    {
        $head = $this->seo->render(
            "Minhas contas fixas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );
        
        echo $this->view->render("recurrences", [
            "head" => $head,
            "invoices" => (new AppInvoice())->find("user_id = :user AND Type IN('fixed_income', 'fixed_expense')","user={$this->user->id}")->fetch(true)
        ]);
    }

    ##########################
     /** MODAL LANÇAMENTO **/
    ##########################
    public function launch(array $data): void
    {
        if (request_limit("applaunch", 200, 60*5)) {
            $json["menssage"] = $this->message->warning("Foi muito rápido {$this->user->first_name}! Por favor aguarde 5 minutos para novos lançamentos.")->render();
            echo json_encode($json);
            return;
        }

        if (!empty($data['enrollments']) && ($data['enrollments'] < 2 || $data['enrollments'] > 420) ) {
            $json["menssage"] = $this->message->warning("Ooops {$this->user->first_name}! Para lanchar o número de parcelas deve ser entre 2 e 420.")->render();
            echo json_encode($json);
            return;
        }

        $data = filter_var_array($data, FILTER_SANITIZE_SPECIAL_CHARS);
        $status = (date($data['due_at']) <= date("Y-m-d") ? "paid" : "unpaid");

        $invoice = (new AppInvoice());
        $invoice->user_id = $this->user->id;
        $invoice->wallet_id = $data['wallet'];
        $invoice->category_id = $data['category'];
        $invoice->invoice_of = null;
        $invoice->description = $data['description'];
        $invoice->type = ($data['repeat_when'] === "fixed" ? "fixed_{$data['type']}" : $data['type'] );
        $invoice->value = str_replace([".",","],["","."], $data['value']);
        $invoice->currency = $data['currency'];
        $invoice->due_at = $data['due_at'];
        $invoice->repeat_when = $data['repeat_when'];
        $invoice->period = (!empty($data['period']) ? $data['period'] : "month");
        $invoice->enrollments = (!empty($data["enrollments"]) ? $data["enrollments"] : 1);
        $invoice->enrollment_of = 1;
        $invoice->status = ($data['repeat_when'] === "fixed" ? "paid" : $status);
        
        if (!$invoice->save()) {
            $json["message"] = $invoice->message()->before("Ooops! ")->render();
            echo json_encode($json);
            return;
        }

        if ($invoice->repeat_when === "enrollment") {
            $invoiceOf = $invoice->id;
            for ($enrollment = 1; $enrollment < $invoice->enrollments; $enrollment++) {
                $invoice->id = null;
                $invoice->invoice_of = $invoiceOf;
                $invoice->due_at = date("Y-m-d", strtotime($data["due_at"] . "+{$enrollment}month"));
                $invoice->status = (date($invoice->due_at) <= date("Y-m-d") ? "paid" : "unpaid");
                $invoice->enrollment_of = $enrollment + 1;
                
                if (!$invoice->save()) {
                    $json["message"] = $invoice->message()->before("Ooops! ")->render();
                    echo json_encode($json);
                    return;
                }
            }
        }

        if ($invoice->type === "income") {
            $this->message->success("Receita lançada com sucesso, Use o filtro para controlar.")->render();
        } else {
            $this->message->success("Despesa lançada com sucesso, Use o filtro para controlar.")->render();
        }
        $json["reload"] = true;
        echo json_encode($json);
        
    }
    
    ##########################
     /** PRECISO DE AJUDA **/
    ##########################
    public function support(array $data): void
    {
        if (empty($data['message'])) {
            $json["message"] = $this->message->warning("Ooops! Você esqueceu de escrever sua mensagem.")->render();
            echo json_encode($json);
            return;
        }

        if(request_limit("appsupport", 300, 60*10)) {
          $json["message"] = $this->message->warning("Por favor, aguarde 10 minutos para enviar novos contatos, sugestões ou reclamações")->render();
          echo json_encode($json);
          return;
      }
            
        if (request_repeat("message", $data['message'])) {
            $json["message"] = $this->message->warning("Já recebemos sua solicitação {$this->user->first_name}. Agradecemos pelo contato e responderemos em breve.")->render();
            echo json_encode($json);
            return;
          }

        $subject = date_fmt() . " - {$data['subject']}";
        $message = filter_var($data['message'], FILTER_SANITIZE_SPECIAL_CHARS);

        $view = new View(__DIR__ . "/../../shared/views/email");
        $body = $view->render("mail",[
            "subject"=>$subject,
            "message"=>str_textarea($message)
            ]);
        
        $envio = (new Email());
        $envio->bootstrap(
            $subject,
            $body,
            CONF_MAIL_SUPPORT,
            "Suporte " . CONF_SITE_NAME
        )->queue();

        //$envio->sendQueue();
        
        $this->message->success("Recebemos sua Solicitação {$this->user->first_name}. Agradecemos seu contato e responderemos em breve.")->flash();
        $json['reload'] = true;
        echo json_encode($json);

        
    }

    ##########################
      /** PAGINA FATURAR **/
    ##########################
    public function invoice(array $data): void
    {
        if (!empty($data["update"])) {

            $invoice = (new AppInvoice())->find("user_id = :user AND id = :id", "user={$this->user->id}&id={$data['invoice']}")->fetch();

            if (!$invoice) {
                $json["message"] = $this->message->error("Ooops! Não foi possível carregar a fatura {$this->user->first_name}. Você pode tentar novamente.")->render();
                echo json_encode($json);
                return;
            }

          if ($data["due_day"] < 1 || $data["due_day"] > $dayOfMonth = date("t", strtotime($invoice->due_at))) {
            $json["message"] = $this->message->warning("O vencimento deve ser entre dia 1 e dia {$dayOfMonth} para este Mês.")->render();
            echo json_encode($json);
            return;
          }

          $data = filter_var_array($data, FILTER_SANITIZE_SPECIAL_CHARS);
          $due_day = date("Y-m", strtotime($invoice->due_at)) . "-" . $data["due_day"];
          $invoice->category_id = $data["category"];
          $invoice->description = $data["description"];
          $invoice->value =  str_replace([".",","], ["","."], $data["value"]);
          $invoice->due_at = date("Y-m-d", strtotime($due_day));
          $invoice->wallet_id = $data["wallet"];
          $invoice->status = $data["status"];

          if(!$invoice->save()) {
            $json["message"] = $invoice->message()->before("Ooops! ")->after(" {$this->user->first_name}.")->render();
            echo json_encode($json);
            return;
          }

          $invoiceOf = (new AppInvoice())->find("user_id = :user AND invoice_of = :of", "user={$this->user->id}&of={$invoice->id}")->fetch(true);

          if (!empty($invoiceOf) && in_array($invoice->type, ["fixed_income", "fixed_expense"])) {
                foreach ($invoiceOf as $invoiceItem) {
                    if ($data["status"] === "unpaid" && $invoiceItem->status === "unpaid") {
                        $invoiceItem->destroy();
                    } else {
                        $due_day = date("Y-m", strtotime($invoiceItem->due_at)) . "-" . $data["due_day"];
                        $invoiceItem->category_id = $data["category"];
                        $invoiceItem->description = $data["description"];
                        $invoiceItem->wallet_id = $data["wallet"];

                        if ($invoiceItem->status === "unpaid") {
                            $invoiceItem->value =  str_replace([".",","], ["","."], $data["value"]);
                            $invoiceItem->due_at = date("Y-m-d", strtotime($due_day));
                        }

                        $invoiceItem->save();

                    }
                }
          }

            $json["message"] = $this->message->success("Pronto {$this->user->first_name}, a atualização foi efetuada com sucesso.")->render();
            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
            "Aluguel - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        $invoice = (new AppInvoice())->find("user_id = :user AND id = :invoice", ":user={$this->user->id}&:invoice={$data['invoice']}")->fetch();

        if (!$invoice) {
            $this->message->error("Ooops! Você tentou acessar uma fatura que não existe")->flash();
            redirect("/app");
        }

        echo $this->view->render("invoice", [
            "head" => $head,
            "invoice"=> $invoice,
            "wallets"=>(new AppWallet())->find("user_id = :user", "user={$this->user->id}", "id, wallet")->order("wallet")->fetch(true),
            "categories"=> (new AppCategory)->find("type = :type", "type={$invoice->category()->type}")->order("order_by")->fetch(true)
        ]);
    }

    /**
    * @param array $data
    * @return void
    */
    public function remove(array $data): void
    {
        $invoice = (new AppInvoice())->find("user_id = :user AND id = :id", "user={$this->user->id}&id={$data["invoice"]}")->fetch();

        if($invoice)
        {
            $invoice->destroy();
            $this->message->success("Tudo pronto {$this->user->first_name}. O lançamento foi removido com sucesso!")->flash();
            $json["redirect"] = url("/app");
            echo json_encode($json);
            return;
        }

        $this->message->error("A fatura não existe ou foi removida!")->flash();
        $json["redirect"] = url("/app");
        echo json_encode($json);
    }
    
    ##########################
      /** PAGINA PERFIL **/
    ##########################
    public function profile(?array $data): void
    {

        if (!empty($data['update'])) {
            list($d, $m, $y) = explode("/", $data['datebirth']);
            $user = (new User())->findById($this->user->id);
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->genre = $data['genre'];
            $user->datebirth = "{$y}-{$m}-{$d}";
            $user->document = preg_replace("/[^0-9]/", "", $data['document']);

            if (!empty($_FILES["photo"])) {
                $file = $_FILES["photo"];
                $upload = new Upload();
                
                if ($this->user->photo()) {
                    (new Thumb())->flush("storage/{$this->user->photo}");
                    $upload->remove("storage/{$this->user->photo}");
                }

                if (!$user->photo = $upload->image($file, "{$user->first_name}{$user->last_name}" . time(), 360)) {
                    $json['message'] = $upload->message()->before("Ooops {$this->user->first_name}! ")->after(".")->render();
                    echo json_encode($json);
                    return;
                }
            }

            if (!empty($data["password"])) {
                if (empty($data["password_re"]) || $data["password"] != $data["password_re"]) {
                    $json['message'] = $this->message->warning("Para alterar sua senha, informe e repita a nova senha!")->render();
                    echo json_encode($json);
                    return;
                }

                $user->password = $data["password"];
            }

            if(!$user->save()) {
                $json['message'] = $user->message()->render();
                echo json_encode($json);
                return;
            }
            
            $json['message'] = $this->message->success("Pronto {$this->user->first_name}. Seus dados foram atualizado com Sucesso.")->render();
            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
            "Meu perfil - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("profile", [
            "head" => $head,
            "user" => $this->user,
            "photo" => ($this->user->photo() ? image($this->user->photo, 360, 360) : theme("/assets/images/avatar.jpg", CONF_VIEW_APP))
        ]);
    }

    ##########################
      /** PAGINA SAIR **/
    ##########################
    public function logout()
    {
        (new Message())->info("Você saiu com sucesso " . Auth::user()->first_name . ". Volte logo :)")->flash();

        Auth::logout();
        redirect("/entrar");
    }
}