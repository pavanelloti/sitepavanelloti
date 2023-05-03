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

        $blog = (new Post())->find();
        $pager = new Pager(url("/blog/p/"));
        $pager->pager(count($blog->fetch(true)), 9, ($data['page'] ?? 1));

        echo $this->view->render("blog", [
            "head"=>"$head",
            "blog"=>$blog->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator"=>$pager->render()
        ]);
    }
    
    public function blogSearch(array $data):void 
    {
        if(!empty($data['s'])){
            $search = filter_var($data['s'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            echo json_encode(["redirect" => url("/blog/buscar/{$search}/1")]);
            return ;
        }
        if(empty($data['terms'])){
            redirect("/blog");
        }

        $search = filter_var($data['terms'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $page = (filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] : 1 );

        $head = $this->seo->render(
            "Pesquisa por {$search} - " . CONF_SITE_NAME,
            "Confira os resultados de sua pesquisa para {$search}",
            url("/blog/buscar/{$search}/{$page}"),
            theme("/assets/images/share.jpg")
        );

        $blogSearch = (new Post())->find("(title LIKE :s OR subtitle LIKE :s)", "s=%{$search}%");
        $result = $blogSearch->fetch(true);
        
        if(!$result){
            echo $this->view->render("blog", [
                "head"=>$head,
                "title"=>"Pesquisa por:",
                "search"=>$search
            ]);
            return ;
        }

        $pager = new Pager(url("/blog/buscar/{$search}/"));
        $pager->pager(count($result), 9, $page);

        echo $this->view->render("blog", [
            "head"=>$head,
            "title"=>"Pesquisa por:",
            "search"=>$search,
            "blog"=>$blogSearch->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator"=>$pager->render()
        ]);
        
    }

    public function blogPost(array $data): void
    {
        $post = (new Post())->findByUri($data['uri']);
        if(!$post){
            redirect("/404");
        }

        $post->views += 1;
        $post->save();

        $head = $this->seo->render(
            "{$post->title} - " . CONF_SITE_NAME,
            $post->subtitle,
            url("/blog/{$post->uri}"),
            image($post->cover, 1200, 628)
        );

        echo $this->view->render("blog-post", [
            "head"=>$head,
            "post"=>$post,
            "related"=>(new Post())
            ->find("category = :category And id != :id", "category={$post->category}&id={$post->id}")
            ->order("rand()")
            ->limit(3)
            ->fetch(true)
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