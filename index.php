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
$route->get("/blog", "Web:blog");
$route->get("/blog/page/{page}", "Web:blog");
$route->get("/blog/{postName}", "Web:blogPost");

//login
$route->get("/entrar", "Web:login");
$route->get("/recuperar", "Web:forget");
$route->get("/cadastrar", "Web:register");

//opçoes
$route->get("/confirma", "Web:confirm");
$route->get("/obrigado", "Web:success");

//serviços
$route->get("/termos", "Web:terms");

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
