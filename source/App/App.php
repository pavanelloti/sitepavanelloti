<?php

namespace Source\App;

use Source\Core\Controller;
use Source\Models\Auth;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;
use Source\Support\Message;


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

        echo $this->view->render("home", [
            "head" => $head
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