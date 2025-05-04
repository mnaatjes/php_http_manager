<?php
    /*----------------------------------------------------------*/
    /**
     * Controller Base Class utilizing __invoke() magic method.
     */
    /*----------------------------------------------------------*/
    abstract class InvokeController {
        /**
         * @var object model
         */
        protected object $model;
        /**
         * @var object $errors
         */
        protected object $errors;
        /*----------------------------------------------------------*/
        /**
         * Constructor
         */
        /*----------------------------------------------------------*/
        public function __construct(object $model){
            /**
             * Invoke Model
             * Test construct working with __invoke()
             */
            $this->model = $model;
            /**
             * Set errors
             */
            $this->errors = new Errors();
        }
        /*----------------------------------------------------------*/
        /**
         * Invoke Magic Method
         * - Routes class method by REQUEST_METHOD
         * - Implements CRUD 
         */
        /*----------------------------------------------------------*/
        public function __invoke(Request $req, Response $res, array $args=[]){
            /**
             * Grab method
             * Switch statement for REQUEST_METHOD
             */
            switch(strtoupper($req->getMethod())){
                /**
                 * POST - Create: Store()
                 */
                case 'POST':
                    return $this->store($req, $res, $args);
                /**
                 * GET - Read: Index(), Show()
                 */
                case 'GET':
                    if(empty($args)){
                        return $this->index($req, $res);
                    } else {
                        return $this->show($req, $res, $args);
                    }
                /**
                 * PUT - Update: edit()
                 */
                case 'PUT':
                    return $this->edit($req, $res, $args);
                /**
                 * DELETE - Delete: destroy() 
                 */
                case 'DELETE':
                    return $this->destroy($req, $res, $args);
                /**
                 * Default
                 * Error
                 */
                default:
                    return $this->onError($req, $res);
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Parses Query array and returns Conditions and ORDER BY arrays
         * @param array $query Query Params from $_GET
         * @return array{
         *      conditions: array,
         *      order_by: array
         * } Array of Conditions and array of ORDER BY properties
         */
        /*----------------------------------------------------------*/
        protected function parseQueryParams(array $query_params){
            /**
             * Parse Query parameters
             * - Check for period delimiters
             * - Parse into arrays
             * @var array $conditions Assoc Array of column_name => value pairs
             * @var array $order_by Assoc Array of ORDER BY statements with col_name => direction pairs
             */
            $conditions = [];
            $order_by   = [];
            var_dump($query_params);
            // Loop query parameters
            foreach($query_params as $key => $value){
                /**
                 * Check for comma delimiters in value
                 */
                $pos_comma = strpos($value, ',');
                if(is_int($pos_comma)){
                    
                }
                /**
                 * Check for delimiter in $key
                 * Check for delimiter in $value
                 * - Find str pos
                 * - Parse parts
                 * - Validate DESC or ASC
                 */
                $pos_key = strpos($key, ":");
                $pos_val = strpos($value, ":");
                // Check key pos
                if(is_int($pos_key)){
                    /**
                     * Check param and direction
                     * Assign Conditions and ORDER BY
                     * - Parse param
                     * - Validate Sort Direction
                     */
                    $param  = substr($key, 0, $pos_key);
                    $dir    = strtoupper(substr($key, $pos_key + 1));
                    // Validate Sort Direction
                    if($dir === "ASC" || $dir === "DESC"){
                        $order_by[$param] = $dir;
                    }
                    // Assign Values
                    $conditions[$param] = $value;
                } else if(is_int($pos_val)){
                    /**
                     * Parse property (key) and param:direction (value)
                     * - Validate "sort" in property (key)
                     * - Validate sort direction
                     */
                    $prop   = strtolower($key);
                    $param  = substr($value, 0, $pos_val);
                    $dir    = strtoupper(substr($value, $pos_val + 1));
                    if($prop === 'sort' || $prop === 'orderby'){
                        // Validate sort direction
                        if($dir === 'ASC' || $dir === 'DESC'){
                            $order_by[$param] = $dir;
                        }
                        /**
                         * Skip to next
                         * Do NOT assign condition
                         */
                        continue;
                    }
                } else {
                    /**
                     * Check for "sort" and "orderBy"
                     */
                    if(strtolower($key) === "sort" || strtolower($key) === "orderby"){
                        $order_by[$value] = strtoupper("ASC");
                    } else {
                        /**
                         * Apply regular param => value condition
                         */
                        $conditions[$key] = $value;
                    }
                }
            }
            /**
             * Return array
             */
            return [
                'conditions' => $conditions,
                'order_by'   => $order_by
            ];
        }
        /*----------------------------------------------------------*/
        /**
         * POST Store Method
         * @param object $req HTTP Request Object
         * @param object $res HTTP Response Object
         * @param array $args Assoc Array of Arguments: params, etc. from Router()
         */
        /*----------------------------------------------------------*/
        abstract protected function store(Request $req, Response $res, array $args=[]);
        /*----------------------------------------------------------*/
        /**
         * GET Read Method for all records
         * @param object $req HTTP Request Object
         * @param object $res HTTP Response Object
         */
        /*----------------------------------------------------------*/
        abstract protected function index(Request $req, Response $res);
        /*----------------------------------------------------------*/
        /**
         * GET Read Method for specific record(s)
         * @param object $req HTTP Request Object
         * @param object $res HTTP Response Object
         * @param array $args Assoc Array of Arguments: params, etc. from Router()
         */
        /*----------------------------------------------------------*/
        abstract protected function show(Request $req, Response $res, array $args);
        /*----------------------------------------------------------*/
        /**
         * PUT Read Method to update / edit a specific resource
         * @param object $req HTTP Request Object
         * @param object $res HTTP Response Object
         * @param array $args Assoc Array of Arguments: params, etc. from Router()
         */
        /*----------------------------------------------------------*/
        abstract protected function edit(Request $req, Response $res, array $args);
        /*----------------------------------------------------------*/
        /**
         * DELETE method to delete / destroy a specific record
         * @param object $req HTTP Request Object
         * @param object $res HTTP Response Object
         * @param array $args Assoc Array of Arguments: params, etc. from Router()
         */
        /*----------------------------------------------------------*/
        abstract protected function destroy(Request $req, Response $res, array $args);
        /*----------------------------------------------------------*/
        /**
         * Default: On Error condition
         * @param object $req HTTP Request Object
         * @param object $res HTTP Response Object
         */
        /*----------------------------------------------------------*/
        abstract protected function onError(Request $req, Response $res);
    }
?>