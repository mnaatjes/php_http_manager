<?php
    /*----------------------------------------------------------*/
    /**
     * HTTP Router Object
     * - TODO: Add default response header option / method / config array / etc
     * - TODO: Add rules validation
     * - TODO: Add config assoc array integration
     * 
     * @since 1.0   Implementation
     * @since 2.0   Overview:
     * - Moved $handler calling in dispatch() to parsing method callHandler() to manage different handler data-types
     */
    /*----------------------------------------------------------*/
    class Router {
        /**
         * List of supported Request Methods
         * @var array SUPPORTED_METHODS
         */
        const SUPPORTED_METHODS = [
            'POST',
            'PUT',
            'GET',
            'DELETE'
        ];
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
         * 
         * @since 1.0
         * @since 2.0   Overview:
         *      - Allows application of handlers for All Routes using using HttpManager::use() wildcard (NULL to "*")
         *      - Define a wildcard: NULL to "*"
         *      - Added mechanism for appending multiple handlers
         *      - Modified $handler param to allow for more than just "callables" (strings: e.g. "UserController@index)
         * 
         * @param string $method
         * @param null|string $path If path === NULL, handler applied to all paths
         * @param string|array|object|callable $handler
         * 
         * @return void
         */
        /*----------------------------------------------------------*/
        public function addRoute(string $method, ?string $path, string|array|object|callable $handler): void{
            /**
             * Validate method
             */
            $method = strtoupper($method);
            if(!in_array($method, VALID_HTTP_METHODS)){
                throw new Error('Invalid method');
            }
            /**
             * Append Wildcard (NULL) paths:
             * - Check if null
             * - Check for existing
             * - Check existing data type
             * - Add to routes
             */
            if(is_null($path)){
                /**
                 * Set wildcard "*" from NULL
                 */
                $path = $this->setWildcard();
                if(isset($this->routes[$method][$path])){
                    /**
                     * Wildcard exists at method:
                     * - Merge handlers
                     * - Add merged handlers to routes array
                     */
                    $this->routes[$method][$path] = $this->mergeHandlers($handler, $this->routes[$method][$path]);
                } else {
                    /**
                     * No existing route(s) at $method[NULL]
                     * - Push to routes
                     */
                    $this->routes[$method][$path] = $handler;
                }
            } else if(is_string($path)){
                /**
                 * Validate string path
                 */
                if(!validate_resource_path($path)){
                    throw new Error('Invalid Resource Path!');
                };
                /**
                 * Append route to routes array
                 * - Normalize Path
                 * - Determine if $path is already in use (if so, append - DON'T override)
                 */
                $path = $this->normalizeResourcePath($path);
                /**
                 * Check if $method[$path] already exists:
                 * - Determine if $handler value is an array or single element
                 * - Convert to array and push new handlers
                 */
                if(isset($this->routes[$method][$path])){
                    /**
                     * Handler exists at route:
                     * - Merge existing handlers with incoming handlers at path
                     * - Append to routes
                     */
                    $this->routes[$method][$path] = $this->mergeHandlers($handler, $this->routes[$method][$path]);
                } else {
                    /**
                     * First entry as $method[$path]
                     * Single handler as element (not array) for value
                     */
                    $this->routes[$method][$path] = $handler;
                }
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Merges existing route handlers with incoming handlers
         * 
         * @since 2.0   Overview: 
         * - Creates a consistent mode of combining incoming and existing handlers
         * - Maintains order of incoming and existing handlers
         * - Casts incoming and existing handlers(s) as arrays for merging
         * - Returns an array of handlers in consistent order
         * 
         * @param string|array|callable $incoming Handler to be added to results array of handlers
         * @param null|string|array|callable $existing Handlers existing in routes or some other array | array or handlers
         * 
         * @return array Assigns handlers to array in FIFO order
         */
        /*----------------------------------------------------------*/
        protected function mergeHandlers(string|array|callable $incoming, null|string|array|callable $existing){
            /**
             * Get type of $incoming handler(s)
             */
            $incoming = is_array($incoming) ? $incoming : [$incoming];
            /**
             * Get type of $existing handler(s):
             * - Check if set
             */
            if(!isset($existing)){
                $existing = [];
            } else if(!is_array($existing)){
                $existing = [$existing];
            }
            /**
             * Merge and return
             */
            return array_merge($existing, $incoming);
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Set wildcard character for $path
         * 
         * @return string Wildcard character for all paths: "*"
         */
        /*----------------------------------------------------------*/
        protected function setWildcard(){return "*";}
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Gets the wildcard character for all paths
         * @uses $this->setWildcard to return character
         * 
         * @return string Wildcard character for all paths: "*"
         */
        /*----------------------------------------------------------*/
        protected function getWildcard(){return $this->setWildcard();}
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Checks if the provided path has Wildcard character set
         * @param null|string $path
         * @return bool Checks for "*" string (true) or NULL / other string (false)
         */
        /*----------------------------------------------------------*/
        protected function isWildcard(string $path){
            return is_string($path) && (is_int(strpos($path, "*")) && strlen($path) === 1);
        }
        /*----------------------------------------------------------*/
        /**
         * Debugging: Returns routes array
         */
        /*----------------------------------------------------------*/
        public function getRoutes(){return $this->routes;}
        /*----------------------------------------------------------*/
        /**
         * Match handler(s) to routes and extract parameters if present in Query string
         * 
         * - First-match wins order:
         *  1) Exact Match:
         *      - Isset()
         *  2) Iterative by method:
         *      - Check for parameters
         *      - Preg-match URI
         *      - Extract $handler function
         *      - Extract parameter if present and translate placeholder (e.g. {id}) if present
         * 
         * @param string $method Request Method
         * @param string $path Resource Path
         * @return null|array Assoc array of matched values on success | NULL on failure
         * 
         * @since 2.0:  Overview:
         * - Added $results array{handlers: null, params: null} container declaration to collect handlers
         * - Added check for $path === NULL and $path === wildcard "*"
         * - Removed validation for type of handler; e.g. is_callable{};
         * - Handler now assigned to $results array without validation of type
         * - Returns NULL on failure (not false)
         * - Validation and parsing of handlers now performed in $this->executeHandlers()
         * 
         * @param string $method Http Request Method
         * @param string $path URI Resource Path
         * 
         * @return null|array{
         *      handlers: null|array,
         *      params: null|array
         * } Array of handlers found in routes array
         */
        /*----------------------------------------------------------*/
        protected function match(string $method, ?string $path): ?array{
            /**
             * Declare $results container array
             */
            $results = [
                'handlers'  => null,
                'params'    => null
            ];
            /**
             * Check for wildcard for All paths
             * - Check routes for $method[wildcard] "*"
             * - Apply to $results container
             * - Check if array existing
             */
            if(isset($this->routes[$method][$this->getWildcard()])){
                /**
                 * Match exists:
                 * - Declare existing container for handler(s) $match
                 * - Evaluate type
                 * - Append to $results array
                 */
                $match = $this->routes[$method][$this->getWildcard()];
                $results['handlers'] = is_array($match) ? $match : [$match];
            }
            /**
             * Check for matches within the routes array:
             * - 1st order: check exact match to $method and $path
             * - 2nd order: check match to $method and preg_match parameters
             */
            if(isset($this->routes[$method][$path])){
                /**
                 * 1st order Match exists: $method with $path
                 * - Get handler(s) match as array
                 * - Declare existing handlers in $results array
                 * - Merge match with existing (from $results)
                 */
                $match      = is_array($this->routes[$method][$path]) ? $this->routes[$method][$path] : [$this->routes[$method][$path]];
                $existing   = $results['handlers'];
                // Append to outgoing $results
                $results['handlers'] = $this->mergeHandlers($match, $existing);
            } else if(isset($this->routes[$method]) && !isset($this->routes[$method][$path])){
                /**
                 * 2nd Order: $method without $path
                 * - Iterate through routes by method
                 * - Iterate by path
                 * - Convert route patter (if present, e.g. {id}) to regex pattern (e.g. {123})
                 * - Attempt to match regex pattern
                 */
                foreach($this->routes[$method] as $subject => $handler){
                    /**
                     * Create regex pattern from definition in routes
                     * - Escape directory separators
                     * - Replace parameter name with regex expression
                     * - Evaluate path
                     */
                    $subject = preg_quote($subject, "/");
                    $replace = '\{(\w+)\}';
                    $pattern = '/' . preg_replace('/\\\{(\w+)\\\}/', $replace, $subject) . '/';
                    /**
                     * Check path for null
                     */
                    if(is_null($path)){
                        $path = '/';
                    }
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
                         */
                        if(count($keys) === count($values)){
                            /**
                             * Assign $results[handlers] array
                             * - Check for existing route handlers
                             * - Push to $results
                             */
                            $results['handlers'] = $this->mergeHandlers($handler, $results['handlers']);
                            /**
                             * Assign parameters to $results[params]
                             * - Combine keys with values
                             * - Push to params
                             */
                            $results['params'] = array_combine($keys, $values);
                        }
                    }
                }
            }
            /**
             * Evaluate Results and return:
             * - Check if handlers is null
             * - Set $results to NULL if no handlers found
             */
            if(!isset($results['handlers']) && is_null($results['handlers'])){
                $results = null;
            }
            /**
             * Return default: array or null
             */
            return $results;
        }
        /*----------------------------------------------------------*/
        /**
         * Calls handler parameters by type
         * 
         * @since 2.0:
         * - Check type of handler: array, object, string (parsed for validation), callable
         * 
         * @param array|object|string|callable $handlers
         * @param array $params
         * @param int $index Default 0; auto-incremented based on number of $handlers (if array) and $next() calls by user
         * @return void
         */
        /*----------------------------------------------------------*/
        private function executeHandlers(array|object|string|callable $handlers, ?array $params, int $index=0){
            /**
             * Define Current Handler:
             * - Declare $has_next boolean to determine if $next is passed; default false
             * - Determine if Array
             * - Define $current
             */
            //var_dump($handlers);
            $has_next = false;
            // Check array
            if(is_array($handlers)){
                /**
                 * Handlers Array:
                 * - Validate index
                 * - Declare current method from $index
                 * - Declare $next() method
                 */
                if(!array_key_exists($index, $handlers)){
                    throw new Exception(sprintf('Index: %s does NOT exist! in %s. No handler to execute!', $index, __FUNCTION__));
                }
                // Define $current
                $current    = $handlers[$index];
                $has_next   = isset($handlers[$index + 1]) ? true : false;
                /**
                 * Anonymous function $next which advances the $current handler in an array of $handlers
                 * 
                 * @param void
                 * 
                 * @uses int $index by reference
                 * @uses array $handlers array(reversed)
                 * @uses array $params
                 * 
                 * @return void
                 * @throws Exception if $next() employed by user when $index exhausted
                 */
                $next = function() use(&$index, $handlers, $params){
                    if($index < count($handlers)){
                        $this->executeHandlers($handlers, $params, $index + 1);
                    } else {
                        throw new Exception('End of handlers array! No next middleware to advance to in '.__FUNCTION__);
                    }
                };
            } else {
                /**
                 * Single handler
                 */
                $current = $handlers;
            }
            /**
             * Sort by handler type
             * - Callable, Invocable Object
             * - Object
             * - String representing Object; e.g. "UserController@index"
             */
            if(is_callable($current)){
                /**
                 * Callable function or invocable object:
                 * - Determine if $next() available
                 */
                if($has_next === false){
                    // Invoke without $next()
                    call_user_func(
                        $current, 
                        $this->req, 
                        $this->res,
                        $params
                    );
                } else {
                    // Invoke with $next()
                    call_user_func(
                        $current, 
                        $this->req, 
                        $this->res,
                        $next,
                        $params
                    );
                }
            } else if(is_string($current)){
                /**
                 * Parse handler string; e.g. UserController@index
                 * - Check for '@' delimiter for class vs class with method
                 * - Grab class and method strings
                 * - Validate class and method exist
                 * - Invoke class
                 */
                if(is_int(strpos($current, '@'))){
                    /**
                     * Class with method
                     * - Explode at delimiter
                     * - Validate class and method
                     */
                    list($obj, $method) = explode('@', $current);
                    if(class_exists($obj) && method_exists($obj, $method)){
                        /**
                         * Invoke Class with method:
                         * - Declare $controller to house class
                         * - call user function $method
                         */
                        $controller = new $obj();
                        /**
                         * Determine if $next() parameter available
                         */
                        if($has_next === false){
                            // Execute without $next()
                            call_user_func(
                                [$controller, $method],
                                $this->req,
                                $this->res,
                                $params
                            );
                        } else {
                            // Execute with $next()
                            call_user_func(
                                [$controller, $method],
                                $this->req,
                                $this->res,
                                $next,
                                $params
                            );
                        }
                    } else {
                        /**
                         * Failed to identify Class with method
                         */
                        throw new Exception('Unable to parse Class @ method in '.__FUNCTION__);
                    }
                } else {
                    /**
                     * Class call without method
                     * - TODO: Currently this condition is not possible due to parsing in http_manager->use()
                     */
                }
            } else {
                throw new TypeError('Handler if of invalid type! Not a string or callable method or class in '.__FUNCTION__);
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Dispatch Route
         * TODO: Automate response codes on failure
         * - Dispatch error handling (code, message, content-type)
         * TODO: Integrate Errors object
         * 
         * @since 1.0   Initial Version:
         * - Handles callable function or invocable objects
         * - Only permits one method per path
         * - No method for next() behavior
         * 
         * @since 2.0   Overview:
         * - Added execute handler
         * - Added next() behavior
         * - Return from $this->match now array{handlers: array, params: array|null} OR null
         * 
         * @return void
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
                $this->executeHandlers($match['handlers'], isset($match['params']) ? $match['params'] : []);
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