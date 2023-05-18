<?php

namespace Source\App;

use Source\Models\Auth;
use Source\Models\User;
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
        $chartData->expense = "1,0,5,0,2";
        $chartData->income = "0,2,0,10,0";

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
        //var_dump($chartData);
        //END CHART

        echo $this->view->render("home", [
            "head" => $head,
            "chart"=> $chartData
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