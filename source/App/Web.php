<?php

namespace Source\App;

use Source\Models\Auth;
use Source\Models\Post;
use Source\Models\User;
use Source\Core\Session;
use Source\Support\Pager;
use Source\Core\Controller;
use Source\Models\Category;
use Source\Models\Faq\Question;
use Source\Models\Report\Access;
use Source\Models\Report\Online;

class Web extends Controller
{
    public function __construct()
    {     
        //redirect("/ops/manutencao");

        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/");

       (new Access())->report();
       (new Online())->report();   
       $session = new Session() ;
       //var_dump($session->all());
        //$email = new Email();
        //$email->bootstrap("Envio de e-mail teste de Fila " . time() , "Teste de Fila de E-mail ", "pavanelloti@gmail.com", "Alex Pavanello" )->sendQueue();


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
    
    public function blogCategory(array $data): void
    {
        $categoryUri = filter_var($data['category'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $category = (new Category())->findByUri($categoryUri);

        if(!$category) {
            redirect("/blog");
        }

        $blogCategory = (new Post())->find("category = :c", "c={$category->id}");
        $page = (!empty($data['page']) && filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] : 1);
        $pager = new Pager(url("/blog/em/{$category->uri}/"));
        $pager->pager($blogCategory->count(), 9, $page);

        $head = $this->seo->render(
            "Artigos em {$category->title} - " . CONF_SITE_NAME,
            $category->description,
            url("/blog/em/{category->uri}/{$page}"),
            ($category->cover ? image($category->cover, 1200, 628) : theme("assetes/image/share.jpg"))
        );

        echo $this->view->render("blog",[
            "head"=>$head,
            "title"=>"Artigos em {$category->title} - ",
            "desc"=>$category->description,
            "blog"=>$blogCategory
                ->limit($pager->limit())
                ->offset($pager->offset())
                ->order("post_at DESC")
                ->fetch(true),
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

        $blogSearch = (new Post())->find("MATCH(title, subtitle) AGAINST(:s)", "s={$search}");
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
    public function login(?array $data): void
    {
        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (request_limit("weblogin", 3, 300)){
                $json['message'] = $this->message->error("Você já efetuou 3 tentativas, esse é o limite. Por favor aguarde 5 minutos para tentar novamente.")->render();
                echo json_encode($json);
                return;
            }

            if (empty($data['email']) || empty($data['password'])) {
                $json['message'] = $this->message->warning("Informe e-mail ou senha para entrar")->render();
                echo json_encode($json);
                return;
            }

            $save = (!empty($data['save']) ? true : false);
            $auth = new Auth();
            $login = $auth->login($data['email'], $data['password'], $save);

            if ($login){
                $json['redirect'] = url("/app");
            } else {
                $json['message'] = $auth->message()->render();
            }

            echo json_encode($json);
            return;
        }

        $head = $this->seo->render("Entar - ".CONF_SITE_NAME, CONF_SITE_DESC, url("/entrar"), theme("/assets/images/share.jpg"));

        echo $this->view->render("auth-login", [
            "head"=>"$head",
            "cookie"=>filter_input(INPUT_COOKIE, "authEmail")
        ]);
    }
    
    public function forget(?array $data): void
    {

        if (!empty($data['csrf'])){
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (empty($data['email'])){
                $json['message'] = $this->message->warning("Informe seu e-mail para recuperar sua senha.")->render();
                echo json_encode($json);
                return;
            }

            if (request_repeat("webforget", $data['email'])) {
                $json['message'] = $this->message->error("Opps! git Você já tentou esse e-mail antes.")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            if ($auth->forget($data['email'])) {
                $json['message'] = $this->message->success("Acesse seu e-mail para recuperar a senha")->render();
            } else {
                $json['message'] = $auth->message()->render();
            }

            echo json_encode($json);
            return;
        }

        $head = $this->seo->render("Recuperar Senha - ".CONF_SITE_NAME, CONF_SITE_DESC, url("/recuperar"), theme("/assets/images/share.jpg"));

        echo $this->view->render("auth-forget", [
            "head"=>$head
        ]);
    }
    
    public function reset(array $data): void 
    {
        if (!empty($data['csrf'])){
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (empty($data['password']) || empty($data['password_re'])) {
                $json['message'] = $this->message->warning("Informe e repita a senha para continuar.")->render();
                echo json_encode($json);
                return;
            }

            list($email, $code) = explode("|", $data['code']);
            $auth = new Auth();

            if ($auth->reset($email, $code, $data['password'], $data['password_re'])) {
                $this->message->success("Senha Alterar com sucesso, Vamos Controlar")->flash();
                $json['redirect'] = url("/entrar");
            } else {
                $json['message'] = $auth->message()->render();
            }

            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
            "Crie sua nova senha - ".CONF_SITE_NAME,
             CONF_SITE_DESC, 
            url("/recuperar"), 
            theme("/assets/images/share.jpg"));

        echo $this->view->render("auth-reset", [
            "head"=>$head,
            "code"=> $data['code']
        ]);
    }

    public function register(?array $data): void
    {

        if(!empty($data['csrf'])){
            if(!csrf_verify($data)){
                $json["message"] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }
        
            if(in_array("", $data)){
                $json['message'] = $this->message->warning("Informe seus dados para criar sua conta.")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            $user = new User();
            $user->bootstrap(
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['password'],
            );
            
            if ($auth->register($user)){
                $json['redirect'] = url("/confirma");
            }else{
                $json['message'] = $auth->message()->render();
            }
            echo json_encode($json);
            return;

        }
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

        echo $this->view->render("optin", [
            "head"=>"$head",
            "data"=>(object)[
                "title"=>"Falta pouco! Confirme seu cadastro.",
                "desc"=>"Enviamos um link de confirmação para seu e-mail. Acesse e siga as instruções para concluir seu cadastro e comece a controlar com o CaféControl",
                "image"=>theme("/assets/images/optin-confirm.jpg")
            ]
        ]);
    }
    
    public function success(array $data): void
    {
        $email = base64_decode($data["email"]);

        $user = (new User())->findByEmail($email);
        
        if ($user && $user->status != "confirmed") {
            $user->status = "confirmed";
            $user->save();     
        }
        
        $head = $this->seo->render("Bem-vindo ao ".CONF_SITE_NAME, CONF_SITE_DESC, url("/obrigado"), theme("/assets/images/share.jpg"));

        echo $this->view->render("optin", [
            "head"=>"$head",
            "data"=>(object)[
                "title"=>"Tudo pronto. Você já pode controlar :)",
                "desc"=>"Bem-vindo(a) ao seu controle de contas, vamos tomar um café?",
                "image"=>theme("/assets/images/optin-success.jpg"),
                "link"=> url("/entrar"),
                "linkTitle"=>"Fazer Login"
            ]
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