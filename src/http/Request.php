<?php
    /*----------------------------------------------------------*/
    /**
     * HTTP Request Object.
     * - Contains the properties and methods of an incoming HTTP Request.
     * - Provides sanitation and validation for incoming data.
     * 
     * @since 1.0   Initial Implementation
     * @since 2.0   Overview:
     *  - Made parseBody() protected
     * 
     */
    /*----------------------------------------------------------*/
    class Request {
        /**
         * @var array|null Configuration assoc array for $_SERVER params
         */
        public ?array $config = null;
        /**
         * @var array|null Validated Array from $_SERVER data
         */
        public ?array $serverParams = null;
        /**
         * @var object Headers
         */
        public $headers = null;
        /**
         * @var string Method from REQUEST_METHOD
         */
        public ?string $method = null;
        /**
         * @var bool Checks if HTTPS used
         */
        public bool $isSecure = false;
        /**
         * @var bool Checks if AJAX call used
         */
        public bool $isAJAX = false;
        /**
         * @var string PATH_INFO derived from URI
         */
        public ?string $pathInfo = null;
        /**
         * @var array Query string from URI
         */
        public ?string $query_str = null;
        /**
         * @var object $uri
         */
        public object $uri;
        /**
         * @var array Errors Object
         */
        public ?object $errors = null;

        /*----------------------------------------------------------*/
        /**
         * Constructor.
         * - Capture $_SERVER parameters
         * - Sanitize, validate, and parse HTTP headers
         * 
         * @param array|null $config Configuration assoc array
         */
        /*----------------------------------------------------------*/
        public function __construct(?array $config=null){
            /**
             * Parse / Validate Config
             */
            $this->config       = $this->validateConfig($config);
            /**
             * Parse / Validate $_SERVER parameters
             */
            $this->serverParams = $this->getServerParams($this->config);
            /**
             * Initialize Request Headers Object
             */
            $this->headers      = new Headers($this->serverParams, false, $this->config);
            $this->method       = $this->serverParams['REQUEST_METHOD'];
            $this->isSecure     = $this->checkSecure();
            $this->isAJAX       = $this->checkAJAX();
            $this->pathInfo     = array_key_exists('PATH_INFO', $this->serverParams) ? $this->serverParams['PATH_INFO'] : null;
            $this->query_str    = array_key_exists('QUERY_STRING', $this->serverParams) ? $this->serverParams['QUERY_STRING'] : null;
            /**
             * Initialize URI object
             */
            $this->uri          = new UriInterface([
                    'host'   => $this->headers->get('host'),
                    'port'   => $this->headers->get('port'),
                    'scheme' => $this->headers->get('scheme')
                ],
                [
                    'request_uri'   => $this->getServerParam('request_uri'),
                    'script_name'   => $this->getServerParam('script_name'),
                    'document_root' => $this->getServerParam('document_root'),
                    'server_name' => $this->getServerParam('server_name'),
            ]);
            /**
             * Initialize Errors object for Request
             */
            $this->errors       = new Errors($this->config['errors']);
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Validates structure of $config array
         * TODO: Finish
         * @param array|null $config
         * @return array Validated $config array
         */
        /*----------------------------------------------------------*/
        private function validateConfig(?array $config=null): array{
            return [
                'errors' => [
                    'log'       => true,
                    'display'   => false,
                    'send'      => true
                ],
            ];
        }
        /*----------------------------------------------------------*/
        /**
         * Sanitizes, validates $_SERVER properties
         * @param array|null $config Configuration assoc array
         * @return array Validated array of $_SERVER data
         */
        /*----------------------------------------------------------*/
        private function getServerParams(?array $config=null){
            return sanitize_server_data($config);
        }
        /*----------------------------------------------------------*/
        /**
         * Returns server param
         */
        /*----------------------------------------------------------*/
        public function getServerParam(string $propName){
            /**
             * Normalize value
             */
            $propName = strtoupper($propName);
            if(key_exists($propName, $this->serverParams)){
                return $this->serverParams[$propName];
            } else {
                return null;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: 
         * - Uses $this->serverParams
         * @param void
         * @return bool
         */
        /*----------------------------------------------------------*/
        private function checkAJAX(){
            if(isset($this->serverParams['HTTP_X_REQUESTED_WITH'])){
                return strtolower($this->serverParams['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            }
            /**
             * Return Default
             */
            return false;
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: 
         * - Uses $this->serverParams
         * @param void
         * @return bool
         */
        /*----------------------------------------------------------*/
        private function checkSecure(): bool{
            // Check for HTTPS
            if(isset($this->serverParams['HTTPS'])){
                // Check setting
                if(is_numeric($this->serverParams['HTTPS'])){
                    // Check for numeric on
                    return intval($this->serverParams['HTTPS']) === 1;
                } else {
                    // Check for string on
                    return strtolower($this->serverParams['HTTPS']) === 'on';
                }
            }
            /**
             * Return Default
             */
            return false;
        }
        /*----------------------------------------------------------*/
        /**
         * @return string REQUEST_METHOD
         */
        /*----------------------------------------------------------*/
        public function getMethod(){return $this->getServerParam('request_method');}
        /*----------------------------------------------------------*/
        /**
         * Utility Method: 
         * @param void
         * @return string Client IP
         */
        /*----------------------------------------------------------*/
        public function getClientIP(){
            // Declare IP Container
            $ip = null;
            // Check possible sources
            if(isset($this->serverParams['HTTP_CLIENT_IP'])){
                $ip = $this->serverParams['HTTP_CLIENT_IP'];
            } else if (isset($this->serverParams['HTTP_X_FORWARDED_FOR'])){
                $ip = $this->serverParams['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $this->serverParams['REMOTE_ADDR'];
            }
            /**
             * Return default
             */
            return $ip;
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: 
         * @param 
         * @return 
         */
        /*----------------------------------------------------------*/
        public function getFiles(){
            if(isset($_FILES)){
                return $_FILES;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Returns $_GET superglobal
         * TODO: Validate
         * TODO: Implement Ruleset validation
         * @param 
         * @return array|null
         */
        /*----------------------------------------------------------*/
        public function getQuery(){
            if(isset($_GET)){
                return $_GET;
            } else {
                return null;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Parse Query Parameters for RESTful API parameters
         * @param void $_GET
         * @return array{
         *      clauses: array,
         *      filters: array,
         *      query: array
         * } parsed clauses and conditions(filters) and remaining query array
         */
        /*----------------------------------------------------------*/
        public function parseQueryRESTful(){
            /**
             * @uses parse_query_parameters()
             * @file utils/parse_query_parameters.php
             */
            return parse_query_parameters($this->getQuery());
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: 
         * @param 
         * @return 
         */
        /*----------------------------------------------------------*/
        public function getPost(){
            if(isset($_POST)){
                return $_POST;
            } else {
                return null;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: 
         * @param 
         * @return 
         */
        /*----------------------------------------------------------*/
        public function getCookie(){
            if(isset($_COOKIE)){
                return $_COOKIE;
            } else {
                return null;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: 
         * @param string $body_string
         * @return string|array|null
         * 
         * @since 1.0 Initial Implementation
         * @since 2.0 Made protected method
         */
        /*----------------------------------------------------------*/
        protected function parseBody(string $body_string){
            /**
             * Grab Content-Type
             * Determine format of body data based on content type
             */
            $content_type = $this->headers->get('content-type');
            // Evaluate by Content-Type
            switch($content_type){
                /*----------------------------------------------------------*/
                /**
                 * JSON
                 */
                /*----------------------------------------------------------*/
                case 'application/json':
                    // Parse JSON
                    $data = json_decode($body_string, true);
                    if($data === null && json_last_error() !== JSON_ERROR_NONE){
                        return ['error' => 'Invalid JSON Data!'];
                    } else {
                        return $data;
                    }
                /*----------------------------------------------------------*/
                /**
                 * Linked Data in JSON format
                 * JSON API Specification
                 */
                /*----------------------------------------------------------*/
                case 'application/ld+json':
                case 'application/vnd.api+json':
                    return $body_string;
                /*----------------------------------------------------------*/
                /**
                 * Default HTML forms via POST without enctype set
                 * Simple Data
                 * Represents data formatted with key-value pairs and URL-encoded
                 */
                /*----------------------------------------------------------*/
                case 'application/x-www-form-urlencoded':
                    parse_str($body_string, $data);
                    return $data;
                /*----------------------------------------------------------*/
                /**
                 * Plain Text
                 */
                /*----------------------------------------------------------*/
                case 'text/plain':
                    return $body_string;
                /*----------------------------------------------------------*/
                /**
                 * HTML
                 */
                /*----------------------------------------------------------*/
                case 'text/html':
                    /***
                     * Check for parser and attempt to extract html
                     */
                    if(extension_loaded('dom')){
                        $dom = new DOMDocument();
                        // set errors
                        libxml_use_internal_errors(true);
                        // Load html string
                        $dom->loadHTML($body_string);
                        // Clear errors
                        libxml_clear_errors();
                        /**
                         * Validate DOM created
                         */
                        if(!$dom){
                            return ['error' => 'Unable to extract HTML document from body string data'];    
                        }
                        /**
                         * Return DOM object for further extraction
                         */
                        return $dom;
                    } else {
                        // Return error
                        return ['error' => 'Unable to load HTML Parser!'];
                    }
                /*----------------------------------------------------------*/
                /**
                 * XML: 
                 * - Application
                 * - Text
                 */
                /*----------------------------------------------------------*/
                case 'application/xml':
                case 'text/xml':
                    /**
                     * Check for parser
                     * Parse string
                     * Validate Data
                     */
                    if(extension_loaded('simplexml')){
                        // Set errors
                        libxml_use_internal_errors(true);
                        // Grab data
                        $data = simplexml_load_string($body_string);
                        // Validate
                        if($data !== false){
                            // Convert xml to json and return
                            return json_decode(json_encode($data), true);
                        }
                        // Clear XML errors
                        libxml_clear_errors();
                    } else {
                        // return default
                        return $body_string;
                    }
                /*----------------------------------------------------------*/
                /**
                 * CSV
                 */
                /*----------------------------------------------------------*/
                case 'text/csv':
                    /**
                     * Parse rows from csv string
                     * Declare results array
                     * Loop rows
                     */
                    $csv = str_getcsv($body_string);
                    if(!is_array($csv)){
                        return ['error' => 'Unable to parse CSV string'];
                    }
                    /**
                     * Return CSV
                     */
                    return $csv;
                /*----------------------------------------------------------*/
                /**
                 * Document Files
                 * - PDF
                 * - ZIP
                 */
                /*----------------------------------------------------------*/
                case 'application/zip':
                case 'application/pdf':
                    return $body_string;
                /*----------------------------------------------------------*/
                /**
                 * Form Data
                 * File Upload Data
                 */
                /*----------------------------------------------------------*/
                case 'multipart/form-data':
                    parse_str($body_string, $data);
                    return $data;
                /*----------------------------------------------------------*/
                /**
                 * Default: Returns Raw Body Data
                 */
                /*----------------------------------------------------------*/
                default:
                    return $body_string;
            }
            /**
             * Return default
             */
            return null;
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Retrieve, parse, and validate raw body and return parsed version
         * @param void
         * @return string|array|null Parsed body content; Null if empty
         */
        /*----------------------------------------------------------*/
        public function getBody(){
            /**
             * Validate php://input
             * Call parseBody
             */
            $body_string = file_get_contents('php://input');
            if(empty($body_string)){
                return null;
            }
            /**
             * Parse body string
             * Return
             */
            return $this->parseBody($body_string);
        }
    }
?>