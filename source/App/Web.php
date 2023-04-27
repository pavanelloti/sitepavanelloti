<?php

namespace Source\App;

use Source\Models\Post;
use Source\Models\User;
use Source\Core\Connect;
use Source\Support\Pager;
use Source\Core\Controller;
use Source\Models\Category;
use Source\Models\Faq\Channel;
use Source\Models\Faq\Question;

class Web extends Controller
{
    public function __construct()
    {
       
       //redirect("/ops/manutencao");
        
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/");

    }
    
    #######################
    /** paginas da home **/
    #######################
    public function home(): void
    {
        
        $head = $this->seo->render(CONF_SITE_NAME . " - " . CONF_SITE_TITLE, CONF_SITE_DESC, url(), theme("/assets/images/share.jpg"));

        echo $this->view->render("home", [
            "head"=>"$head",
            "video"=>"lDZGl9Wdc7Y",
            "blog"=> (new Post())->find()->order("post_at DESC")->limit(9)->fetch(true)
        ]);
    }

    public function about(): void
    {
        $head = $this->seo->render(CONF_SITE_NAME . " - " . CONF_SITE_TITLE, CONF_SITE_DESC, url("/sobre"), theme("/assets/images/share.jpg"));

        echo $this->view->render("about", [
            "head"=>"$head",
            "video"=>"lDZGl9Wdc7Y",
            "faq"=>(new Question())
                ->find("channel_id = :id", "id=1", "question, response")
                ->order("order_by")
                ->fetch(true)
        ]);
    }
   
    public function cadastro(): void
    {
        $head = $this->seo->render(CONF_SITE_NAME . " - " . CONF_SITE_TITLE, CONF_SITE_DESC, url("/cadastro"), theme("/assets/images/share.jpg"));

        echo $this->view->render("home", [
            "head"=>"$head",
            "video"=>"lDZGl9Wdc7Y"
        ]);
    }

    #######################
    /** paginas do blog **/
    #######################
    public function blog(?array $data): void
    {
        $head = $this->seo->render("Blog - " . CONF_SITE_NAME, CONF_SITE_DESC, url("/blog"), theme("/assets/images/share.jpg"));

        $pager = new Pager(url("/blog/page/"));

        $pager->pager(100, 10, ($data['page'] ?? 1));

        echo $this->view->render("blog", [
            "head"=>"$head",
            "paginator"=>$pager->render()
        ]);
    }
    
    public function blogPost(array $data): void
    {
        $postName = $data["postName"];
        
        $head = $this->seo->render("POSTNAME - " . CONF_SITE_NAME, CONF_SITE_DESC, url("/blog/{$postName}"), theme("/assets/images/share.jpg"));

        echo $this->view->render("blog-post", [
            "head"=>"$head",
            "data"=>$this->seo->data()
        ]);
    }

    ########################
    /** paginas dp login **/
    ########################
    public function login()
    {
        $head = $this->seo->render("Entar - ".CONF_SITE_NAME, CONF_SITE_DESC, url("/entrar"), theme("/assets/images/share.jpg"));

        echo $this->view->render("auth-login", [
            "head"=>"$head"
        ]);
    }
    
    public function forget()
    {
        $head = $this->seo->render("Recuperar Senha - ".CONF_SITE_NAME, CONF_SITE_DESC, url("/recuperar"), theme("/assets/images/share.jpg"));

        echo $this->view->render("auth-forget", [
            "head"=>"$head"
        ]);
    }
    
    public function register()
    {
        $head = $this->seo->render("Cadastre-se - ".CONF_SITE_NAME, CONF_SITE_DESC, url("/cadastrar"), theme("/assets/images/share.jpg"));

        echo $this->view->render("auth-register", [
            "head"=>"$head"
        ]);
    }

    #########################
    /** paginas de opções **/
    #########################
    public function confirm()
    {
        $head = $this->seo->render("Confirme seu Cadastro - ".CONF_SITE_NAME, CONF_SITE_DESC, url("/confirme"), theme("/assets/images/share.jpg"));

        echo $this->view->render("optin-confirm", [
            "head"=>"$head"
        ]);
    }
    
    public function success()
    {
        $head = $this->seo->render("Bem-vindo ao ".CONF_SITE_NAME, CONF_SITE_DESC, url("/obrigado"), theme("/assets/images/share.jpg"));

        echo $this->view->render("optin-success", [
            "head"=>"$head"
        ]);
    }

    ###########################
    /** paginas de serviços **/
    ###########################
    public function terms(): void
    {
        $head = $this->seo->render(CONF_SITE_NAME . " - Termos de uso.", CONF_SITE_DESC, url("/termos"), theme("/assets/images/share.jpg"));

        echo $this->view->render("terms", [
            "head"=>"$head"
        ]);
    }

    ########################
    /** paginas de error **/
    ########################
    public function error(array $data): void
    {

        switch ($data['errcode']) {
            case 'problemas':
                $error = new \stdClass();
                $error->code = "Ops";
                $error->title = "Estamos enfrentando problemas!";
                $error->message = "Parece que nosso serviço não está disponível no momento. Já estamos vendo isso mas caso precise, nos envie um e-mail :)";
                $error->linkTitle = "Enviar E-Mail";
                $error->link = "mailto:" . CONF_MAIL_SUPPORT;
                break;
            case 'manutencao':
                $error = new \stdClass();
                $error->code = "Ops";
                $error->title = "Desculpe. Estamos em Manutenção!";
                $error->message = "Voltamos logo! Por hora estamos trabalhando para melhorar nosso conteúdo para você controlar melhor as suas contas :P";
                $error->linkTitle = null;
                $error->link = null;
                break;
            default:
                $error = new \stdClass();
                $error->code = $data['errcode'];
                $error->title = "Ooops, Conteúdo indiposnível :/";
                $error->message = "Sentimos muito, mas o conteúdo que você tentou acessar não existe, não está indisponível no momento ou foi removido :/";
                $error->linkTitle = "Continuar navegando";
                $error->link = url_back();
                break;
        }
        
        
        $head = $this->seo->render("{$error->code} | {$error->title}", $error->message, url("/ops/{$error->code}"), theme("/assets/images/share.jpg"), false);
        
        echo $this->view->render("error", [
            "head"=>"$head",
            "error"=>$error
        ]);
    }

}