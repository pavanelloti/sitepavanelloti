<?php

namespace Source\App;

use Source\Core\View;
use Source\Models\Auth;
use Source\Models\Post;
use Source\Models\User;
use Source\Support\Email;
use Source\Core\Controller;
use Source\Support\Message;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\CafeApp\AppInvoice;


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

    ##########################
      /** PAGINA RECEBER **/
    ##########################
    public function income()
    {
        $head = $this->seo->render(
            "Minhas receitas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("income", [
            "head" => $head
        ]);
    }

    ##########################
       /** PAGINA PAGAR **/
    ##########################
    public function expense()
    {
        $head = $this->seo->render(
            "Minhas despesas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("expense", [
            "head" => $head
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
    public function invoice()
    {
        $head = $this->seo->render(
            "Aluguel - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("invoice", [
            "head" => $head
        ]);
    }

    ##########################
      /** PAGINA PERFIL **/
    ##########################
    public function profile()
    {
        $head = $this->seo->render(
            "Meu perfil - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("profile", [
            "head" => $head
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