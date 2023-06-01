<?php
ob_start();

require __DIR__ . "/vendor/autoload.php";

use Source\Core\Session;
use CoffeeCode\Router\Router;

$session = new Session();
$route = new Router(url(), ":");

##################
/** WEB ROUTES **/
##################

//home
$route->namespace("Source\App");
$route->get("/", "Web:home");
$route->get("/sobre", "Web:about");

//blog
$route->group("/blog");
$route->get("/", "Web:blog");
$route->get("/p/{page}", "Web:blog");
$route->get("/{uri}", "Web:blogPost");
$route->post("/buscar", "Web:blogSearch");
$route->get("/buscar/{terms}/{page}", "Web:blogSearch");
$route->get("/em/{category}", "Web:blogCategory");
$route->get("/em/{category}/{page}", "Web:blogCategory");

//login
$route->group(null);
$route->get("/entrar", "Web:login");
$route->post("/entrar", "Web:login");
$route->get("/cadastrar", "Web:register");
$route->post("/cadastrar", "Web:register");
$route->get("/recuperar", "Web:forget");
$route->post("/recuperar", "Web:forget");
$route->get("/recuperar/{code}", "Web:reset");
$route->post("/recuperar/resetar", "Web:reset");


//opçoes
$route->get("/confirma", "Web:confirm");
$route->get("/obrigado/{email}", "Web:success");

//serviços
$route->get("/termos", "Web:terms");

//APP
$route->group("/app");
$route->get("/", "App:home");
$route->get("/receber", "App:income");
$route->get("/receber/{status}/{category}/{date}", "App:income");
$route->get("/pagar", "App:expense");
$route->get("/pagar/{status}/{category}/{date}", "App:expense");
$route->get("/fatura/{invoice}", "App:invoice");
$route->get("/perfil", "App:profile");
$route->get("/sair", "App:logout");

$route->post("/launch", "App:launch");
$route->post("/invoice/{invoice}", "App:invoice");
$route->post("/support", "App:support");
$route->post("/onpaid", "App:onpaid");
$route->post("/filter", "App:filter");



####################
/** ERROR ROUTES **/
####################

$route->namespace("Source\App")->group("/ops");
$route->get("/{errcode}", "Web:error");

##############
/** ROUTES **/
##############

$route->dispatch();

######################
/** ERROR REDIRECT **/
######################

if($route->error()){
    $route->redirect("/ops/{$route->error()}");
}
ob_end_flush();
