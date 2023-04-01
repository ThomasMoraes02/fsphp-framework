<?php

use Source\Core\Session;
use CoffeeCode\Router\Router;

ob_start();

require_once __DIR__ . "/vendor/autoload.php";

// ini_set("display_errors", 1);

/**
* BOOTSTRAP
*/

$session = new Session;
$route = new Router(url(), ":");

/**
* WEB ROUTES
*/
$route->namespace("Source\App");
$route->get("/", "Web:home");
$route->get("/sobre", "Web:about");


/**
* ERROR ROUTES
*/
$route->namespace("Source\App")->group("/ops");
$route->get("/{errcode}", "Web:error");

/**
* ROUTE
*/
$route->dispatch();

/**
* ERROR REDIRECT
*/
if($route->error()) {
    $route->redirect("/ops/{$route->error()}");
}


ob_end_flush();