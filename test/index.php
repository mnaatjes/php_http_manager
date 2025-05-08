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
    header('Content-Type: application/json');
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
    /**
     * Users Route
     */
    /*
    $users = $app->route();
    $users  ->get('/items', function($req, $res, $next){var_dump('First Middleware'); $next();})
            ->get('/items', function($req){var_dump('Second Middleware');});
    $app->use('/users', $users);
    */
    //$app->get('/users/stuff', function($req, $res, $next){var_dump('Table of Contents'); $next();});
    //$app->use('/users/items', function($req, $res, $next){var_dump('1st'); $next();});
   //$app->use('/users/items', function($req, $res, $next){var_dump('2nd');});
    /**
     * Apply all
     */
    $app->use(function($req, $res, $next){var_dump('Apply All: First Middleware'); $next();});
    $app->get('/users/items', function($req, $res, $next){var_dump('Hello 1'); $next();});
    $app->get('/users/items', function($req, $res){var_dump('Hello 2');});
    $app->use(function($res, $req, $next){var_dump('Apply All: Second Middleware'); $next();});
    /**
     * Dispatch all routes
     */
    $app->execute();
?>