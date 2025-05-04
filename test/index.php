<?php
    /**
     * Require main
     * - Enable Errors for debugging
     * - 
     */
    require_once('../src/main.php');
    ini_errors_enable();
    $request    = new Request([]);
    $response   = new Response([]);
    $router     = new Router($request, $response, []);
    /**
     * Add Routes
     */
    $router->addRoute('GET', '/', function($req, $res){
        $res->setStatus(200);
        $res->setContentType('application/json');
        $res->setBody(json_encode('Hello'));
        $res->send();
    });
    /**
     * Dispatch
     */
    $router->dispatch();
?>