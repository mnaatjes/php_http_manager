<?php
    /**
     * Test for PHP HTTP Manager
     * @date 05/04/2025
     * 
     * Require main
     * - Enable Errors for debugging
     * - 
     */
    require_once('../src/main.php');
    //header('Content-Type: application/json');
    ini_errors_enable();
    /**
     * Invocable Object
     */
    class TestController {
        public function __invoke(){echo 'Success invocable object at /users';}
        public function index($req, $res, $params){
            var_dump('TestController called with method index()');
            //$next();
        }
    }
    /**
     * Init $app
     */
    $app = new HttpManager();
    $app->get('/', function($req, $res){
        $res->render('test', ['name' => 'Apollo']);
    });
    /**
     * Dispatch all routes
     */
    $app->execute();
?>