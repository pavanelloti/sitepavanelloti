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

//login
$route->group(null);
$route->get("/entrar", "Web:login");
$route->get("/cadastrar", "Web:register");
$route->post("/cadastrar", "Web:register");
$route->get("/recuperar", "Web:forget");


//opçoes
$route->get("/confirma", "Web:confirm");
$route->get("/obrigado/{email}", "Web:success");

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
