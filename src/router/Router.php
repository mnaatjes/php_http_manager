<?php
    /*----------------------------------------------------------*/
    /**
     * HTTP Router Object
     * - TODO: Add default response header option / method / config array / etc
     * - TODO: Add rules validation
     * - TODO: Add config assoc array integration
     */
    /*----------------------------------------------------------*/
    class Router {
        /**
         * @var array|null
         */
        protected array $config;
        /**
         * @var array{
         *      method: string,
         *      path: string,
         *      handler: object|string,
         * } $routes Assoc array of routes
         */
        protected array $routes = [];
        /**
         * @var 
         */
        protected object $res;
        /**
         * @var 
         */
        protected object $req;
        /**
         * @var object 
         */
        public object $errors;
        /*----------------------------------------------------------*/
        /**
         * Constructor
         * @param object $request
         * @param object $response
         * @param array $config
         */
        /*----------------------------------------------------------*/
        public function __construct(object $request, object $response, array $config=[]){
            /**
             * Validate config
             */
            $this->config = $this->validateConfig($config);
            /**
             * Define http objects
             */
            $this->req = $request;
            $this->res = $response;
            /**
             * Declare errors object
             */
            $this->errors = new Errors();
        }
        /*----------------------------------------------------------*/
        /**
         * Validate config
         */
        /*----------------------------------------------------------*/
        private function validateConfig(?array $config){return $config;}
        /*----------------------------------------------------------*/
        /**
         * Private Method: normalize resource path
         * @param string $path
         * @return string
         * @throws TypeError
         */
        /*----------------------------------------------------------*/
        private function normalizeResourcePath(string $path){
            return $path;
        }
        /*----------------------------------------------------------*/
        /**
         * Add a route
         * @param string $method
         * @param string $path
         * @param callable $handler
         */
        /*----------------------------------------------------------*/
        public function addRoute(string $method, string $path, callable $handler){
            /**
             * Validate method
             */
            $method = strtoupper($method);
            if(!in_array($method, VALID_HTTP_METHODS)){
                throw new Error('Invalid method');
            }
            /**
             * Validate path
             */
            if(!validate_resource_path($path)){
                throw new Error('Invalid Resource Path!');
            };
            /**
             * TODO: Allow handler as array or strings
             * @example 'UserController@index' --> new UserController->index()
             */
            /**
             * Append route to routes array
             * - Normalize Path
             * - Append by method; e.g. GET
             */
            $path = $this->normalizeResourcePath($path);
            $this->routes[$method][$path] = $handler;
        }
        /*----------------------------------------------------------*/
        /**
         * Debugging: Returns routes array
         */
        /*----------------------------------------------------------*/
        public function getRoutes(){return $this->routes;}
        /*----------------------------------------------------------*/
        /**
         * Match to routes.
         * - First-match wins order:
         *  1) Exact Match:
         *      - Isset()
         *  2) Iterative by method:
         *      - Check for parameters
         *      - TODO: Preg-match params
         *      - Preg-match URI
         * Extract $handler function:
         * - Handler as object
         * - TODO: Handler as string
         * - TODO: Handler as class string @ method
         * Parameter matching:
         * - Translate placeholder (if present)
         * - Extract parameter
         * TODO: Add:
         *  - Sanitize
         *  - Validate
         *  - Type casting
         * @return array|false Assoc array of matched values on success | False on failure
         */
        /*----------------------------------------------------------*/
        protected function match(string $method, ?string $path){
            /**
             * 1st Order:
             * Check for exact match within routes array
             */
            if(isset($this->routes[$method][$path])){
                /**
                 * Validate handler:
                 * - Is callable object
                 * - Is string --> parse
                 */
                $handler = $this->routes[$method][$path];
                if(!is_callable($handler)){
                    /**
                     * TODO: Check if string and evaluate
                     * Return default
                     */
                    return false;
                } else {
                    /**
                     * Success:
                     * - Return assoc array from match
                     * - TODO: integrate params
                     */
                    return [
                        'handler' => $handler
                    ];
                }
            }
            /**
             * 2nd Order:
             * - Iterate through routes by method
             * - Iterate by path
             * - Convert route patter (if present, e.g. {id}) to regex pattern (e.g. {123})
             * - Attempt to match regex pattern
             */
            if(isset($this->routes[$method])){
                /**
                 * Loop routes and evaluate for matches
                 */
                $results = [];
                foreach($this->routes[$method] as $subject => $handler){
                    /**
                     * Create regex pattern from definition in routes
                     * - Escape directory separators
                     * - Replace parameter name with regex expression
                     * - Evaluate path
                     * - TODO: FIX!!!!!
                     */
                    $subject = preg_quote($subject, "/");
                    $replace = '\{(\w+)\}';
                    $pattern = '/' . preg_replace('/\\\{(\w+)\\\}/', $replace, $subject) . '/';
                    /**
                     * Check for parameters to parse
                     */
                    if(preg_match($pattern, $path, $match)){
                        /**
                         * Collect parameter name, value pairs and return as array
                         */
                        preg_match_all('/\\\{(\w+)\\\}/', $subject, $keys);
                        $keys   = array_slice($keys, 1)[0];
                        $values = [];
                        foreach($match as $prop){
                            if($prop === $path){
                                continue;
                            }
                            $values[] = $prop;
                        }
                        /**
                         * Validate number of properties
                         * - TODO: Sanitize and validate
                         */
                        if(count($keys) === count($values)){
                            /**
                             * Return array of parameters and handler
                             */
                            $results = [
                                'handler'   => $handler,
                                'params'    => array_combine($keys, $values)
                            ];
                        }
                    }
                }
                /**
                 * Evaluate Results and return
                 */
                if(empty($results)){
                    return null;
                }
                if(!isset($results['handler'])){
                    return null;
                }
                /**
                 * Passed Checks, return results array
                 */
                return $results;
            }
            /**
             * Return Default
             */
            return false;
        }
        /*----------------------------------------------------------*/
        /**
         * Dispatch Route
         * TODO: Automate response codes on failure
         * - Dispatch error handling (code, message, content-type)
         * TODO: Integrate Errors object
         */
        /*----------------------------------------------------------*/
        public function dispatch(){
            /**
             * Capture method and path
             */
            $method = $this->req->getMethod();
            $path   = $this->req->uri->getResourcePath();
            /**
             * Validate with match() method
             */
            $match = $this->match($method, $path);
            /**
             * Validate match
             */
            if(!is_null($match) && is_array($match)){
                /**
                 * Success:
                 * - Evaluate method string (if present)
                 * - Execute handler method
                 * - Check for params
                 */
                call_user_func(
                    $match['handler'], 
                    $this->req, 
                    $this->res, 
                    isset($match['params']) ? $match['params']: []
                );
            } else {
                /**
                 * Failure to dispatch solution:
                 * - Set HTTP response headers
                 * - Set HTTP response body
                 * - Return HTTP status
                 */
                $this->res->setStatus(404);
                $this->res->setContentType('application/json');
                $this->res->setBody(json_encode([
                    'error' => 'Unable to find Resource! Please try again.'
                ]));
                $this->res->send();
            }
        }
    }
?>