<?php

namespace Source\App;

use Source\Core\Controller;

class Web extends Controller
{
    public function __construct()
    {

        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/");

    }

    public function home(): void
    {
        $head = $this->seo->render(CONF_SITE_NAME . " - " . CONF_SITE_TITLE, CONF_SITE_DESC, url(), url("/assets/images/share.jpg"));

        echo $this->view->render("home", [
            "head"=>"$head",
            "video"=>"lDZGl9Wdc7Y"
        ]);
    }

    public function about(): void
    {
        echo $this->view->render("home", [
            "title"=>"Café Control Gerencie suas contas com o melhor café!"
        ]);
    }

    public function cadastro(): void
    {
        echo $this->view->render("home", [
            "title"=>"Café Control Gerencie suas contas com o melhor café!"
        ]);
    }


    public function error(array $data): void
    {

        $error = new \stdClass();
        $error->code = $data['errcode'];
        $error->title = "Ooops, Conteúdo indiposnível :/";
        $error->message = "Sentimos muito, mas o conteúdo que você tentou acessar não existe, está indisponível no momento ou foi removido :/";
        $error->link = url_back();
        $error->linkTitle = "Continuar navegando";
        
        $head = $this->seo->render("{$error->code} | {$error->title}", $error->message, url(), url("/assets/images/share.jpg"), false);
        
        echo $this->view->render("error", [
            "head"=>"$head",
            "error"=>$error
        ]);
    }

}