<?php
    /*----------------------------------------------------------*/
    /**
     * This is the main class for the PHP HTTP Manager
     * 
     * Provides similar functionality to express.js but in php
     * - Router for request and responses
     * - Rendering views
     * - Redirecting
     * 
     * @package HttpManager
     * @author Michael Naatjes <michael.naatjes87@gmail.com>
     * @copyright 2025
     * @license MIT
     * @link 
     * 
     * @version 1.0 Initial Implementation:
     *  - Created structure
     * 
     */
    /*----------------------------------------------------------*/
    class HttpManager {
        /**
         * Configuration array for HTTP Manager; contains configuration for all subsequent objects
         * 
         * @var array $config
         */
        public array $config = [];
        /**
         * @var Request $request
         */
        protected ?object $request;
        /**
         * @var Response $request
         */
        protected ?object $response;
        /**
         * @var Router $router 
         */
        protected Router $router;
        /**
         * Array of Router objects for creating sub-routes
         * 
         * @var array $routes
         */
        public array $routes = [];
        /**
         * @var null|object $viewEngine
         */
        protected ?object $viewEngine;
        /*----------------------------------------------------------*/
        /**
         * Construct:
         * - Created HTTP Request Object
         * - Created HTTP Response Object
         * 
         * @param array $config Configuration array for HTTP Manager Object; Default empty
         * 
         */
        /*----------------------------------------------------------*/
        public function __construct(array $config=[]){
            /**
             * Validate and define config
             */
            $this->config = $this->parseConfig($config);
            /**
             * Declare viewEngine and define default behavior
             */
            $this->viewEngine = new ViewEngine(
                // Directory
                isset($this->config['views']) && is_string($this->config['views']) ? $this->config['views'] : null
            );
            /**
             * Define request and response objects
             */
            $this->request    = new Request($this->config['request']);
            $this->response   = new Response($this->config['response'], $this->viewEngine);
            /**
             * Define Main Router
             */
            $this->router = new Router($this->request, $this->response, $this->config['router']);
        }
        /*----------------------------------------------------------*/
        /**
         * Validation and Parsing of Config array
         * - Defines default properties
         */
        /*----------------------------------------------------------*/
        private function parseConfig(array $config){
            return [
                'request'   => [],
                'response'  => [],
                'router'    => [],
                'views'     => isset($config['views']) ? $config['views'] : null,
                'perms'     => [
                    'unix'  => 0777,
                    'win'   => null,
                    'sys'   => strtoupper(PHP_OS)
                ],
                'errors'    => [],
            ];
        }
        /*----------------------------------------------------------*/
        /**
         * Routing Method: Get
         * 
         * @param string $path
         * @param callable $handler 
         * @return self
         */
        /*----------------------------------------------------------*/
        public function get(string $path, callable $handler): self{
            /**
             * TODO: Validation 
             */
            /**
             * Add route to router
             */
            $this->router->addRoute('GET', $path, $handler);
            /**
             * Return self for method chaining
             */
            return $this;
        }
        /*----------------------------------------------------------*/
        /**
         * Routing Method: Post
         * 
         * @param string $path
         * @param callable $handler 
         * @return self
         */
        /*----------------------------------------------------------*/
        public function post(string $path, callable $handler): self{
            /**
             * TODO: Validation 
             */
            /**
             * Add route to router
             */
            $this->router->addRoute('POST', $path, $handler);
            /**
             * Return self for method chaining
             */
            return $this;
        }
        /*----------------------------------------------------------*/
        /**
         * Routing Method: Put
         * 
         * @param string $path
         * @param callable $handler 
         * @return self
         */
        /*----------------------------------------------------------*/
        public function put(string $path, callable $handler): self{
            /**
             * TODO: Validation 
             */
            /**
             * Add route to router
             */
            $this->router->addRoute('PUT', $path, $handler);
            /**
             * Return self for method chaining
             */
            return $this;
        }
        /*----------------------------------------------------------*/
        /**
         * Routing Method: Delete
         * 
         * @param string $path
         * @param callable $handler 
         * @return self
         */
        /*----------------------------------------------------------*/
        public function delete(string $path, callable $handler): self{
            /**
             * TODO: Validation 
             */
            /**
             * Add route to router
             */
            $this->router->addRoute('DELETE', $path, $handler);
            /**
             * Return self for method chaining
             */
            return $this;
        }
        /*----------------------------------------------------------*/
        /**
         * Set:
         * - Define views and includes
         * - Define view engine
         * 
         */
        /*----------------------------------------------------------*/
        public function set(...$args){

        }
        /*----------------------------------------------------------*/
        /**
         * Use: 
         * 
         * @since 2.0   Initial:
         * - Added
         * - Mount middleware methods
         * - End request/response cycle
         * - Define paths with routes; i.e. mounting main routes to sub-routes
         * - Executes router instances set in route()
         * 
         * @param null|string $path Path for which middleware method is invoked; If NULL, apply to all
         * @param callable|object|array|null [$handler] Middleware method, Route Handlers | Array of middleware methods or middleware stacks
         * 
         * @return void
         * 
         * @throws TypeError Invalid string parameter
         * @throws Exception Too many paths
         */
        /*----------------------------------------------------------*/
        public function use(...$args): void{
            /**
             * Parse arguments into parameters
             */
            $parsed     = $this->parseArguments($args);
            $path       = $parsed['path'];
            $handlers   = $parsed['handlers'];
            $routes     = $parsed['routes'];
            /**
             * Append Routes to main router
             */
            if(!empty($routes)){
                $this->appendRoutes($path, $routes);
            }
            /**
             * Append handlers to $path
             */
            if(!empty($handlers)){
                /**
                 * Determine state of $path parameter:
                 * - NULL: apply to all paths
                 * - String: apply to specific path
                 */
                if(is_null($path)){
                    /**
                     * Append handler to all routes:
                     * - Apply to all Request Methods
                     * - Apply to all routes (NULL)
                     */
                    foreach(Router::SUPPORTED_METHODS as $method){
                        $this->router->addRoute($method, $path, $handlers);
                    }
                } else {
                    /**
                     * Append handlers with path to ALL Http Request Methods using Router object
                     */
                    foreach(Router::SUPPORTED_METHODS as $method){
                        $this->router->addRoute($method, $path, $handlers);
                    }
                }
            }
            return;
        }
        /*----------------------------------------------------------*/
        /**
         * Parses use() arguments
         * @param array $arguments
         * @return array{
         *      
         * } Assoc array of use() parameters
         */
        /*----------------------------------------------------------*/
        private function parseArguments(array $args){
            /**
             * Parse args:
             * - Loop args and parse by type
             * - Parse into container vars $path and $handlers
             * - Check for Route object and collect in container
             * - Validate $path: if > 1, exit
             */
            $count      = 0;
            $path       = null;
            $handlers   = [];
            $routes     = [];
            // Loop arguments
            foreach($args as $arg){
                /**
                 * Validate $path count:
                 * - If greater-than 1 break
                 * - Only allows for 1 path parameter
                 */
                if($count > 1){
                    //Error Handling
                    trigger_error('Multiple paths passed in '. __FUNCTION__. ' Can only contain 1 path parameter');
                    break;
                }
                /**
                 * Check string:
                 * - Parse out path: validate
                 * - Parse out handler object calls (e.g. "UserController@index")
                 */
                if(is_string($arg)){
                    /**
                     * Check is path:
                     * - Leading forward slash
                     * - NO "@" symbol
                     * - Add to count
                     */
                    $pos_at     = strpos($arg, '@');
                    $pos_slash  = strpos($arg, '/');
                    $has_slash  = str_starts_with($arg, '/');
                    if($pos_at === false && ($has_slash === true && $pos_slash === 0)){
                        /**
                         * Valid path
                         */
                        $path = $arg;
                        $count++;
                    } else if(is_int($pos_at) && ($pos_slash === false && $has_slash === false)){
                        /**
                         * Valid Class @ method string and parse handlers
                         */
                        $handlers = array_merge($handlers, $this->parseHandlers($arg));
                    } else {
                        /**
                         * Incorrect formatting of string parameter
                         */
                        throw new TypeError('Cannot properly parse string parameter in '.__FUNCTION__);
                    }
                } else {
                    /**
                     * Parse Arguments not strings:
                     * - Determine if array
                     * - Determine if $arg is a Route object
                     * - Else, parse handlers
                     */
                    if(is_object($arg) && !is_callable($arg)){
                        /**
                         * Determine if object is a controller / middleware class or a Route object
                         */
                        if(method_exists($arg, 'getList') && property_exists($arg, 'is_route')){
                            /**
                             * Object is a Route object:
                             * - Push Route object onto $routes container
                             */
                            $routes[] = $arg;
                        } else {
                            /**
                             * Object is a controller or other middleware class
                             * - Append to $handlers container
                             */
                            $handlers = array_merge($handlers, $this->parseHandlers($arg));
                        }
                    } else if(is_array($arg)){
                        /**
                         * Argument is an array:
                         * - Cycle and determine components
                         * - Determine if handler or route object
                         */
                        foreach($arg as $item){
                            /**
                             * Check if handler or Route object
                             */
                            if(is_object($item) && (method_exists($item, 'getList') && property_exists($item, 'is_route'))){
                                /**
                                 * Route object
                                 */
                                $routes[] = $item;
                            } else {
                                /**
                                 * Parse handlers
                                 * - Objects, callables, strings, etc
                                 */
                                $handlers = array_merge($handlers, $this->parseHandlers($item));
                            }
                        }
                    } else {
                        /**
                         * $arg object or callable and is NOT a Route object
                         * Parse Handlers
                         */
                        $handlers = array_merge($handlers, $this->parseHandlers($arg));
                    }
                }
            }
            /**
             * Validate path:
             * - Validate count - exit > 1
             * - Check for every/empty path argument
             */
            if($count > 1){
                throw new Exception('Path parameters greater than 1 in '.__FUNCTION__.' Only allows for 1 path parameter!');
            }
            /**
             * Set wildcard if $path empty string
             */
            if(is_string($path)){
                if(empty(trim($path))){
                    $path = null;
                }
            }
            /**
             * Validate path type
             */
            if(!is_null($path) && !is_string($path)){
                throw new Exception('Unknown parameter entered in '.__FUNCTION__);
            }
            /**
             * Return parameters
             */
            return [
                'path'      => $path,
                'handlers'  => $handlers,
                'routes'    => $routes
            ];
        }
        /*----------------------------------------------------------*/
        /**
         * Apply routes from Route object
         * @param null|string $path path to append
         * @param array routes
         * @return void
         */
        /*----------------------------------------------------------*/
        protected function appendRoutes(?string $path, array $routes): void{
            /**
             * Validate array of route objects
             */
            $is_valid = array_every($routes, function($_, $obj){
                return is_object($obj) ? (method_exists($obj, 'getList') && property_exists($obj, 'is_route')) : false;
            });
            if($is_valid === false){
                throw new TypeError('Routes parameter does not contain a Route object in '.__FUNCTION__);
            }
            /**
             * Cycle routes and append to main application router object
             */
            foreach($routes as $obj){
                // cycle list of routes
                foreach($obj->getList() as $method => $list){
                    // cycle each route
                    foreach($list as $key => $middleware){
                        /**
                         * Check for wildcard in $path (null):
                         * - NULL path --> apply to all paths
                         */
                        if(is_null($path)){
                            /**
                             * Apply to All paths
                             */
                            $this->router->addRoute($method, NULL, $middleware);
                        } else {
                            /**
                             * Apply directly to $path:
                             */
                            $route = $path . (str_starts_with($key, '/') ? $key : '/' . $key);
                            // append to router
                            $this->router->addRoute($method, $route, $middleware);
                        }
                    }
                }
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Parses and Validates Handlers:
         * @since 2.0   Initialization:
         * - Check data type of parameter
         * - Parse string cases: Class(@)method and normalize string
         * - Parse Objects: callable, classes
         * - Validate and throw error if invalid type or format
         * - Consolidate into one array and return
         * 
         * @param array|object|callable|string $handlers
         * @return array parsed handlers
         */
        /*----------------------------------------------------------*/
        private function parseHandlers($handlers): array{
            /**
             * Declare results container
             */
            $results = [];
            /**
             * Check for null
             */
            if(is_null($handlers)){
                throw new TypeError('No handler methods or references passed in '.__FUNCTION__.'!');
            }
            /**
             * Check for types
             * - Strings
             * - Arrays
             * - Objects
             */
            if(is_string($handlers)){
                /**
                 * Validate and parse string class with method:
                 * - Check for leading forward slash
                 * - Presence of "@" symbol
                 */
                $pos_at     = strpos($handlers, '@');
                $pos_slash  = strpos($handlers, '/');
                $has_slash  = str_starts_with($handlers, '/');
                if(is_int($pos_at) && ($pos_slash === false && $has_slash === false)){
                    /**
                     * Valid Class @ method string:
                     * - Normalize string, remove trailing "(" and ")" if present
                     * - Assign to results array
                     */
                    $pos_open   = strpos($handlers, '(');
                    $pos_close  = strpos($handlers, ')');
                    if((is_int($pos_open) && is_int($pos_close)) || is_int($pos_open) || is_int($pos_close)){
                        $handlers = substr($handlers, 0, is_int($pos_open) ? $pos_open : $pos_close);
                    }
                    $results[] = $handlers;
                } else {
                    /**
                     * Invalid handler string
                     */
                    trigger_error('TypeError: Handler parameter has improperly formatted Class@method in '.__FUNCTION__);
                }
            } else if(is_object($handlers)){
                /**
                 * Append object to results container
                 */
                $results[] = $handlers;
            } else if(is_callable($handlers)){
                /**
                 * Append function to results container
                 */
                $results[] = $handlers;
            } else if(is_array($handlers)){
                /**
                 * Loop and call parsing method recursively:
                 * - Validate element types: break if null | array
                 * - Collect results
                 */
                foreach($handlers as $handler){
                    // Break on invalid type
                    if(is_null($handler) || is_array($handler)){
                        trigger_error('TypeError: Elements of the $handlers array CANNOT be of type Array or NULL in '.__FUNCTION__);
                        break;
                    } else {
                        // Recursively call parse handlers and collect results
                        $results = array_merge($results, $this->parseHandlers($handler));
                    }
                }
            }
            /**
             * Return results array
             */
            return $results;
        }
        /*----------------------------------------------------------*/
        /**
         * Route: Create a chain of sub-routes for a given path
         * - Creates and returns a new route Object for sub-routes
         * @return Anonymous Route Object
         */
        /*----------------------------------------------------------*/
        public function route(): object{
            return new class {
                public bool $is_route   = true;
                private array $list     = [];
                /**
                 * Returns List
                 * @param void
                 * @return array
                 */
                public function getList(){return $this->list;}
                /**
                 * Append GET path with handler
                 * @param string $path
                 * @param callable $handler
                 */
                public function get(string $path, callable $handler): self{
                    // append
                    $this->add('GET', $path, $handler);
                    // return self for method chaining
                    return $this;
                }
                /**
                 * Append POST path with handler
                 * @param string $path
                 * @param callable $handler
                 */
                public function post(string $path, callable $handler): self{
                    // append
                    $this->list['POST'][$path] = $handler;
                    // return self for method chaining
                    return $this;
                }
                /**
                 * Append PUT path with handler
                 * @param string $path
                 * @param callable $handler
                 */
                public function put(string $path, callable $handler): self{
                    // append
                    $this->list['PUT'][$path] = $handler;
                    // return self for method chaining
                    return $this;
                }
                /**
                 * Append PUT path with handler
                 * @param string $path
                 * @param callable $handler
                 */
                public function delete(string $path, callable $handler): self{
                    // append
                    $this->list['DELETE'][$path] = $handler;
                    // return self for method chaining
                    return $this;
                }
                /**
                 * Appends path and handler to route
                 * @param string $method
                 * @param string $path
                 * @param string|array|callable $handler
                 */
                private function add(string $method, string $path, string|array|callable $handler): void{
                    /**
                     * Validate $method
                     */
                    if(!in_array($method, Router::SUPPORTED_METHODS)){
                        throw new Exception('Invalid HTTP Method in '.__FUNCTION__);
                    }
                    /**
                     * Validate path
                     */
                    if(!str_starts_with($path, '/')){
                        throw new Exception('Path provided missing directory separator!');
                    }
                    /**
                     * Append method to route list:
                     * - Check if already set
                     * - Determine if array
                     * - Append
                     */
                    if(isset($this->list[$method][$path])){
                        // Check existing is an array
                        if(is_array($this->list[$method][$path])){
                            $existing = $this->list[$method][$path];
                        } else {
                            $existing = [$this->list[$method][$path]];
                        }
                        // Check if incoming is an array
                        if(is_array($handler)){
                            $handlers = array_merge($handler, $existing);
                        } else {
                            // cast as array
                            $handlers = array_merge([$handler], $existing);
                        }
                        // Assign to list
                        $this->list[$method][$path] = $handlers;
                    } else {
                        /**
                         * Single value at list[$method][$path]
                         */
                        $this->list[$method][$path] = $handler;
                    }
                }
            };
        }
        /*----------------------------------------------------------*/
        /**
         * Renders a php file in the application
         * @param string $view_name of document to be rendered; e.g. for /views/gemini.php, param === "gemini"
         * @param callable $callback Function called once the view has been rendered
         * @param null|array $data Data to be applied to template or file; Default NULL
         * 
         * @return void Renders stream of content with callback
         */
        /*----------------------------------------------------------*/
        public function render(string $view_name, callable $callback, null|array $data=null){
            /**
             * Renders content as string
             * - Use viewEngine->render() which returns content string | null
             * - Declare errors container
             * - Validate content string
             * - Implement callback
             */
            $results = $this->viewEngine->render($view_name, $data, EXTR_OVERWRITE);
            /**
             * Implement Callback with $errors and $content from render()
             */
            $callback($results['errors'], $results['content']);
        }
        /*----------------------------------------------------------*/
        /**
         * Execute Application
         */
        /*----------------------------------------------------------*/
        public function execute(){
            $this->router->dispatch();
        }
    }
?>