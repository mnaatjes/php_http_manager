<?php
    /*----------------------------------------------------------*/
    /**
     * HTTP Header Object.
     * - Sets and Gets headers
     * - Sends Headers
     * - Checks headers
     * 
     * @version 2.0.0
     * @since 1.0.0
     *  - Migrated to php_http_manager repository
     *  - Self contained utilities
     *  - Fixed ['errors'] undefined index for $config
     */
    /*----------------------------------------------------------*/
    class Headers {
        /**
         * @var bool 
         */
        private bool $isResponse = false;
        /**
         * @var array Configuration array
         */
        public array $config;
        /**
         * @var array Headers container
         */
        private ?array $headers = [];
        /**
         * @var object Errors object/array
         */
        public $errors;
        /**
         * @var bool $sent Triggered after $this->send() called
         */
        private bool $sent = false;
        /*----------------------------------------------------------*/
        /**
         * Construct.
         * - Sets initial state of headers
         * 
         * @param array|null $server_params
         * @param bool $is_response
         * @param array $config
         */
        /*----------------------------------------------------------*/
        public function __construct(?array $server_params=null, bool $is_response=false, array $config){
            $this->isResponse   = $is_response;
            $this->config       = $config;
            $this->headers      = $this->parseHeaders($server_params, $is_response);
            $this->errors       = isset($config['errors']) ? new Errors($this->config['errors']) : new Errors();
        }
        /*----------------------------------------------------------*/
        /**
         * Validation Method: Validates whether $is_response is set to true
         * - Disables method if false
         * - On false: records error
         * @param string $func_name
         * @return bool
         */
        /*----------------------------------------------------------*/
        private function isValidMethod(string $func_name): bool{
            /**
             * Check if header instance is a response
             */
            if($this->isResponse === false){
                /**
                 * Response Methods disabled
                 * - Log Error
                 * - Return false
                 */
                $this->errors->add('exception', sprintf('Invalid Method Call! Headers() instance cannot perform Response method: %s()!', $func_name));
                return false;
            } else if ($this->isResponse === true){
                /**
                 * Response Methods enabled
                 */
                return true;
            } else {
                /**
                 * Return default
                 */
                return false;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Parses headers and deposits them in headers container array
         */
        /*----------------------------------------------------------*/
        private function parseHeaders(?array $server_params, bool $is_response){
            /**
             * Validate request
             */
            if($is_response === true){
                return [];
            }
            /**
             * Declare results container
             * Loop server params
             */
            $results = [];
            foreach($server_params as $key => $value){
                /**
                 * Check for Scheme
                 */
                if($key === 'HTTPS' && ($value === 'on' || $value === 1)){
                    $results['scheme'] = 'https';
                } else if($key === 'HTTP_X_FORWARDED_PROTO' && strtolower($value) === 'https'){
                    $results['scheme'] = 'https';
                }
                /**
                 * Check for Port
                 */
                if($key === 'SERVER_PORT'){
                    if(is_numeric($value) && intval($value) > 1 && intval($value) < 65535){
                        $results['port'] = intval($value);
                    } else {
                        // Log invalid port number
                        $this->errors->add('exception', 'Invalid port number from Request!');
                        continue;
                    }
                }
                /**
                 * Format Header Keys
                 * - Strip HTTP from properties
                 * - Return Lowercase Keys
                 */
                if (strpos($key, 'HTTP_') === 0) {
                    /**
                     * Format HTTP_ properties and push
                     */
                    $key = str_replace('HTTP_', '', $key);
                    $results[strtolower($key)] = $value;
                } else if (str_starts_with($key, 'CONTENT_')) {
                    /**
                     * Check CONTENT_TYPE for boundary
                     */
                    if($key === 'CONTENT_TYPE'){
                        /**
                         * Evaluate for boundary property:
                         * - Multipart form data/mixed
                         * - Perform preg_match for boundary
                         */
                        if(strpos($value, 'multipart') > 0){
                            preg_match('/^([^;]+)(?:;\s*boundary=(.+))?$/', $value, $matches);
                            if(count($matches) > 1){
                                // Assign content-boundary
                                $results['content_boundary'] = $matches[2];
                            }
                            // Assign content-type
                            $results[strtolower($key)] = $matches[1];
                        } else {
                            /**
                             * Normal Content-Type
                             */
                            $results[strtolower($key)] = $value;
                        }
                    } else {
                        /**
                         * Grab CONTENT properties
                         * Format and push to array
                         */
                        $results[strtolower($key)] = $value;
                    }
                } else if(str_starts_with($key, 'SERVER_')){
                    $key = substr($key, strlen('SERVER_'));
                    $results[strtolower($key)] = $value;
                }
            }
            /**
             * Validate $results array and return
             */
            if(empty($results) || is_null($results)){
                return null;
            }
            /**
             * Check for absent values
             */
            if(!array_key_exists('scheme', $results)){
                // Set Scheme
                $results['scheme'] = 'http';
            }
            /**
             * Normalize keys
             * Return normalized array
             */
            $normalized = [];
            foreach($results as $key => $value){
                $normalized[$this->normalizeHeader($key)] = $value;
            }
            /**
             * Return normalized array
             */
            return $normalized;
        }
        /*----------------------------------------------------------*/
        /**
         * Private Method: Normalizes header property name
         * @param string $name
         * @return string Normalized property name
         */
        /*----------------------------------------------------------*/
        private function normalizeHeader(string $name){
            /**
             * Normalize:
             * - Camelcase or snake_case to hyphen replace
             * - Capitalize each letter beginning word or after hyphen
             * - Replace underscore "_" or CamelCase with hyphen "-"
             * - Trim
             */
            if(str_has_delimiter($name, '_')){
                $name = str_snake_to_kebab($name, CASE_CAPS);
            } else if(str_has_delimiter($name, '-')){
                // explode and capitalize
                $result = [];
                foreach(explode('-', $name) as $part){
                    $result[] = ucfirst($part);
                }
                $name = implode('-', $result);
            } else {
                $name = str_camel_to_kebab($name, CASE_CAPS);
            }
            /**
             * Alter Ch_Ua to upper
             */
            $name = str_replace_substr($name, 'Ch-Ua', CASE_UPPER);
            /**
             * Trim and return
             */
            return trim($name);
        }
        /*----------------------------------------------------------*/
        /**
         * Checks if header exists
         * @return bool
         */
        /*----------------------------------------------------------*/
        public function has(string $name): bool{
            $name = $this->normalizeHeader($name);
            return isset($this->headers[$name]);
        }
        /*----------------------------------------------------------*/
        /**
         * Returns specified header
         * @param string name Header name
         * @return 
         */
        /*----------------------------------------------------------*/
        public function get(string $name){
            // Normalized
            $name = $this->normalizeHeader($name);
            if(isset($this->headers[$name])){
                return $this->headers[$name];
            } else {
                return null;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Add header to headers array
         */
        /*----------------------------------------------------------*/
        public function set(string $name, $value=null): self{return $this->add($name, $value);}
        public function add(string $name, $value=null): self{
            /**
             * Validate method
             */
            if(!$this->isValidMethod(__FUNCTION__)){
                return $this;
            } else {
                /**
                 * Normalize and add to headers array
                 */
                $name = $this->normalizeHeader($name);
                $this->headers[$name] = $value;
                // Return self
                return $this;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Remove header to headers array and List (if present)
         */
        /*----------------------------------------------------------*/
        public function remove(string $name){
            /**
             * Validate method
             * Validate Sent
             * Remove if valid
             */
            if(!$this->isValidMethod(__FUNCTION__)){
                return $this;
            } else {
                /**
                 * Normalize name
                 * Check if headers already sent
                 */
                $name = $this->normalizeHeader($name);
                if($this->sent()){
                    $this->errors->add('exception', sprintf('Header: %s has already been sent and cannot be removed', $name));
                    return $this;
                } else {
                    /**
                     * Validate isset $name
                     * Remove header from headers array
                     * Remove header from list if set
                     */
                    if($this->has($name)){
                        // Remove from headers array
                        unset($this->headers[$name]);
                        // Remove from headers_list()
                        if($this->inList($name)){
                            header_remove($name);
                        }
                        // Return to self
                        return $this;
                    } else {
                        // Header never set
                        $this->errors->add('argument', sprintf('Header %s does not exist!', $name));
                        return $this;
                    }
                }
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Returns all headers
         */
        /*----------------------------------------------------------*/
        public function getAll(){
            return $this->headers;
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Clears all headers from headers array and headers_list
         * @return self
         */
        /*----------------------------------------------------------*/
        public function clearAll(){
            /**
             * Validate Method
             */
            if(!$this->isValidMethod(__FUNCTION__)){
                // Cannot clear all in request headers
                return $this;
            } else {
                /**
                 * Validate headers sent
                 */
                if($this->sent()){
                    // Headers already sent - cannot clear
                    $this->errors->add('exception', 'Cannot clear headers! Headers already sent!');
                    return $this;
                } else {
                    // Unset all headers in array
                    $this->headers = [];
                    // Clear list
                    $this->clearList();
                    // Return self
                    return $this;
                }
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Clears all headers using header_remove(null)
         * @return self
         */
        /*----------------------------------------------------------*/
        private function clearList(): self{
            /**
             * Validate method
             */
            if(!$this->isValidMethod(__FUNCTION__)){
                // Cannot clear list from Request Headers
                return $this;
            } else {
                // Remove all response headers from headers_list
                header_remove(null); 
                return $this;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Returns headers_list() parsed by property name
         * @return array Parsed headers_list() array in key, value pairs
         */
        /*----------------------------------------------------------*/
        public function getList(): array{
            /**
             * Validate headers_list()
             */
            if(!is_array(headers_list())){
                return [];
            }
            if(empty(headers_list())){
                return [];
            }
            /**
             * Loop headers and split into key, value pairs
             */
            $pairs = [];
            foreach(headers_list() as $item){
                if(strpos($item, ":") !== false){
                    // Separate key from values
                    list($key, $values) = explode(":", $item, 2);
                    // Normalize key
                    $key = $this->normalizeHeader($key);
                    // Explode values by comma
                    $values = explode(",", trim($values));
                    /**
                     * Push to $pairs array:
                     * - Check if $key already exists
                     * - Append $values or merge $values
                     */
                    if(key_exists($key, $pairs)){
                        // Merge with existing value
                        $pairs[$key] = array_merge($pairs[$key], $values);
                    } else {
                        // Header not previously added to $pairs
                        $pairs[$key] = $values;
                    }
                }
            }
            /**
             * Return parsed headers
             */
            return $pairs;
        }
        /*----------------------------------------------------------*/
        /**
         * Validation Method: Checks if headers already sent
         * @return bool False if invalid
         */
        /*----------------------------------------------------------*/
        public function sent(): ?bool{
            /**
             * Validate method
             */
            if(!$this->isValidMethod(__FUNCTION__)){
                return false;
            } else {
                // Check headers method
                if(!headers_sent()){
                    // Check $sent boolean and return value
                    return $this->sent;
                } else {
                    // Sent verified by headers list
                    return true;
                }
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Checks to see if header has been set and exist in headers_list()
         * @param string $name Name of header to check
         * @return bool|null Null if method is invalid
         */
        /*----------------------------------------------------------*/
        public function inList(string $name): ?bool{
            /**
             * Validate Method
             */
            if(!$this->isValidMethod(__FUNCTION__)){
                return null;
            } else {
                $name = $this->normalizeHeader($name);
                return array_key_exists($name, $this->getList());
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Assigns and sends all headers in the headers array using php header()
         * @return self
         */
        /*----------------------------------------------------------*/
        public function send(){
            /**
             * Validate Method
             */
            if(!$this->isValidMethod(__FUNCTION__)){
                return $this;
            } else {
                /**
                 * Validate headers:
                 * - Headers already Sent
                 * - Headers array empty
                 */
                if($this->sent()){
                    // Already sent
                    $this->errors->add('exception', 'Cannot resend headers! Headers already sent!');
                    return $this;
                } else if(!isset($this->headers) || empty($this->headers)){
                    // No headers declared
                    $this->errors->add('type', 'No headers to send!');
                    return $this;
                } else {
                    /**
                     * Prepare and send headers
                     * - Get default protocol and status
                     * - Loop headers array and output
                     */
                    $protocol   = $this->has('protocol') ? $this->get('protocol') : 'HTTP/1.1';
                    $status     = $this->has('status') ? $this->get('status') : 200;
                    // Send Protocol and Status
                    header(sprintf('%s %d %s', $protocol, $status, VALID_HTTP_STATUS[$status]));
                    // Loop and send headers from array
                    foreach($this->headers as $key => $values){
                        header(sprintf('%s: %s', $key, $values));
                    }
                    // Set $sent boolean
                    $this->sent = true;
                    // Return self
                    return $this;
                }
            }
        }
    }
?>
