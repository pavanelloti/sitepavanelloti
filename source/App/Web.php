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
        echo $this->view->render("home", [
            "title"=>"Café Control Gerencie suas contas com o melhor café!"
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
        echo $this->view->render("error", [
            "title"=>"{$data['errcode']} | Ooops"
        ]);
    }

}