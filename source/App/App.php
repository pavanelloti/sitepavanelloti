<?php

namespace Source\App;

use Source\Models\Auth;
use Source\Core\Controller;
use Source\Support\Message;

class App extends Controller
{
 
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_APP);

        //restricao
        if (!Auth::user()){
            $this->message->warning("Faça login para acessar o APP.")->flash();
            redirect("/entrar");
        }
    }

    public function home()
    {
        echo flash();
        Auth::user();
        echo "<br><br><a title='Sai' href='" . url("/app/sair") . "'> Sair </a>";
    }

    public function logout()
    {
        (new Message())->info("Voce saiu com Sucesso " . Auth::user()->first_name . ". Volte Logo :)")->flash();

        Auth::logout();

        redirect("/entrar");
    }

}