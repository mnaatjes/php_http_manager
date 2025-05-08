<?php
    /*----------------------------------------------------------*/
    /**
     * HTTP Response Object.
     * 
     * 
     * @since 1.0   Initial Implementation
     * @since 2.0   Overview:
     *  - Self contained in php_http_manager
     *  - TODO: Modify send() for shorthand (i.e. calculates status and content-type and just takes body, defines, and sends)
     *  - TODO: Create render() method for rendering /views
     * @since 2.1   Added viewEngine parameter
     */
    /*----------------------------------------------------------*/
    class Response {
        /**
         * @var array|null Configuration assoc array for $_SERVER params
         */
        public ?array $config = null;
        /**
         * @var array|null Validated Array from $_SERVER data
         */
        public ?array $serverParams = null;
        /**
         * @var object Errors Object
         */
        public ?object $errors;
        /**
         * @var object Headers Object
         * - Protected: Only able to be accessed within Request Class
         * 
         */
        protected $headers = null;
        /**
         * @var string|null HTTP response body
         */
        protected ?string $body = null;
        /**
         * @var null|object View engine for rendering content
         */
        protected ?object $view;
        /*----------------------------------------------------------*/
        /**
         * Constructor
         * @param 
         */
        /*----------------------------------------------------------*/
        public function __construct(?array $config=null, ?object $view_engine=null){
            /**
             * Set configuration
             */
            $this->config = $this->validateConfig($config);
            /**
             * Extract and parse server parameters
             */
            $this->serverParams = $this->getServerParams($this->config);
            /**
             * Declare Headers object
             */
            $this->headers = new Headers($this->serverParams, true, $this->config);
            /**
             * Declare View Engine if present
             */
            $this->view = $view_engine;
            /**
             * TODO: Fix Error handling
             */
            $this->errors = new Errors($this->config['errors']);
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
                'headers'   => [

                ],
                'errors'    => [
                    'log'       => true,
                    'display'   => true,
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
         * Utility Method to check if a header exists
         * @param string $name Header Name
         * @return bool
         */
        /*----------------------------------------------------------*/
        public function hasHeader(string $name){
            /**
             * Return from headers->has();
             */
            return $this->headers->has($name);
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method to retrieve header value
         * @param string $name Header Name
         * @return string Header value
         */
        /*----------------------------------------------------------*/
        public function getHeader(string $name){
            /**
             * Utilize header->get
             */
            return $this->headers->get($name);
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method to set header value
         * @param string $name Header Name
         * @param string $value Header value
         * @return self
         */
        /*----------------------------------------------------------*/
        public function setHeader(string $name, string $value=''): self{
            /**
             * Utilize header->set
             */
            $this->headers->set($name, $value);
            /**
             * Return default
             */
            return $this;
        }
        /*----------------------------------------------------------*/
        /**
         * Set status
         * @param int $code
         * @return self
         */
        /*----------------------------------------------------------*/
        public function setStatus(int $code=200): self{
            /**
             * Validate code
             */
            if(!in_array($code, array_keys(VALID_HTTP_STATUS))){
                $this->errors->add('type', 'Invalid code for HTTP status setting: ' . $code);
                return $this;
            }
            /**
             * Set in response header
             */
            $this->headers->set('status', $code);
            /**
             * Return self
             */
            return $this;
        }
        /*----------------------------------------------------------*/
        /**
         * Returns status from header
         */
        /*----------------------------------------------------------*/
        public function getStatus(){
            return $this->headers->get('status');
        }
        /*----------------------------------------------------------*/
        /**
         * Sets Content-Type header
         * @param string $type Content type, e.g. "application/json"
         * @return self
         */
        /*----------------------------------------------------------*/
        public function setContentType(string $type){
            /**
             * Validate content type not already set
             */
            if($this->headers->has('content_type')){
                // Trigger error
                $this->errors->add('type', 'Content Type is already set!');
                // return self
                return $this;
            }
            /**
             * Set content type
             * - Validate set
             */
            $this->headers->set('content_type', $type);
            // Validate
            if(!$this->headers->has('content_type')){
                /**
                 * Failure setting http response header
                 */
                $this->errors->add('type', 'Failure setting Content-Type in HTTP Response Header');
                return $this;
            } else {
                /**
                 * Return self
                 */
                return $this;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Sets http response body
         * @param string Http Response Body text stream
         * @return self
         */
        /*----------------------------------------------------------*/
        public function setBody(string $content_string=''): self{
            /**
             * Validate content type is set
             */
            if(!$this->headers->has('content-type')){
                // Set error
                $this->errors->add('type', 'Content-Type not set! Cannot set Body');
                // Return self
                return $this;
            }
            /**
             * Success!
             * - Set body property
             * - Return self
             */
            $this->body = $content_string;
            return $this;
        }
        /*----------------------------------------------------------*/
        /**
         * Set Body from File.
         * - Retrieves file contents
         * - Sets HTTP Body content string from file
         * @param string $filepath
         * @return self
         */
        /*----------------------------------------------------------*/
        public function setBodyFromFile(?string $filepath=null): self{
            /**
             * Validate filepath
             * - Trim
             * - Check for null
             * - Check for empty
             * - Exists
             */
            if(!is_valid_file($filepath)){
                $this->errors->add('Type', sprintf('Provided filepath: %s is NOT a valid file', $filepath));
                return $this;
            } else {
                /**
                 * Validate Content-Type
                 */
                if(!$this->headers->has('content-type')){
                    // Content-type not set!
                    $this->errors->add('Argument', sprintf('Content-type not set in: %s', __FUNCTION__));
                    return $this;
                }
                $content_type   = $this->headers->get('content_type');
                $mime_type      = get_file_mimetype($filepath);
                if($mime_type == $content_type){
                    /**
                     * Get file contents and set http response body
                     * - Grab file contents
                     * - Evaluate
                     * - Set
                     */
                    $contents = file_get_contents($filepath);
                    // Evaluate
                    if(is_string($contents)){
                        /**
                         * Set http body and return self
                         */
                        $this->body = $contents;
                        return $this;
                    } else {
                        /**
                         * Report failure and return self
                         */
                        $this->errors->add('exception', sprintf('Failed to obtain file contents from: %s in %s', $filepath, __FUNCTION__));
                        return $this;
                    }
                } else {
                    /**
                     * Could not validate mime / content types
                     * Return self
                     */
                    $this->errors->add('argument', 
                        sprintf(
                            'Could not pair mime-type and content-type in %s! Check filetype of filepath: %s.',
                            __FUNCTION__, $filepath
                    ));
                    return $this;
                }
                /**
                 * Return default
                 */
                return $this;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * @return string HTTP body
         */
        /*----------------------------------------------------------*/
        public function getBody(){
            return $this->body;
        }
        /*----------------------------------------------------------*/
        /**
         * Renders document using View Engine:
         * - Validates view-engine set
         * - Validates view-name source
         * - Collects content results
         * - Checks for errors
         * - Determines headers: content-type, status
         * - Sets response body
         * - Sends response
         * 
         * @since 2.1 Added
         * 
         * @param string $view_name
         * @param null|array $data Data to be applied to template or file; Default NULL
         * 
         * @return void Renders content and sends http response
         * @throws Exception Cannot render
         */
        /*----------------------------------------------------------*/
        public function render(string $view_name, null|array $data=null): void{
            /**
             * Validate method
             */
            if(is_object($this->view)){
                // Grab content results
                $results = $this->view->render($view_name, $data);
                /**
                 * Validate content and errors
                 */
                if(is_null($results['errors'])){
                    /**
                     * Build Body
                     */
                    $this->setStatus(200);
                    $this->setContentType($results['mime']);
                    $this->setBody($results['content']);
                    $this->send();
                } else {
                    /**
                     * Build Error Response
                     */
                    $this->setStatus(500);
                    $this->setContentType('application/json');
                    $this->setBody(json_encode($results['errors']));
                    $this->send();
                }
            } else {
                throw new Exception('Cannot render content! View engine not set in Response Object!');
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Validates the format of the http response body based on the content-type
         * TODO: Determine if necessary
         * TODO: Figure out how to implement
         * @param string
         * @return string formatted http body
         */
        /*----------------------------------------------------------*/
        private function validateBodyFormat(?string $content_string){
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
                    return true;
                /*----------------------------------------------------------*/
                /**
                 * Linked Data in JSON format
                 * JSON API Specification
                 */
                /*----------------------------------------------------------*/
                case 'application/ld+json':
                case 'application/vnd.api+json':
                    return false;
                /*----------------------------------------------------------*/
                /**
                 * Default HTML forms via POST without enctype set
                 * Simple Data
                 * Represents data formatted with key-value pairs and URL-encoded
                 */
                /*----------------------------------------------------------*/
                case 'application/x-www-form-urlencoded':
                    return false;
                /*----------------------------------------------------------*/
                /**
                 * Plain Text
                 */
                /*----------------------------------------------------------*/
                case 'text/plain':
                    return false;
                /*----------------------------------------------------------*/
                /**
                 * HTML
                 */
                /*----------------------------------------------------------*/
                case 'text/html':
                    return false;
                /*----------------------------------------------------------*/
                /**
                 * XML: 
                 * - Application
                 * - Text
                 */
                /*----------------------------------------------------------*/
                case 'application/xml':
                case 'text/xml':
                    return false;
                /*----------------------------------------------------------*/
                /**
                 * CSV
                 */
                /*----------------------------------------------------------*/
                case 'text/csv':
                    return false;
                /*----------------------------------------------------------*/
                /**
                 * Document Files
                 * - PDF
                 * - ZIP
                 */
                /*----------------------------------------------------------*/
                case 'application/zip':
                case 'application/pdf':
                    return false;
                /*----------------------------------------------------------*/
                /**
                 * Form Data
                 * File Upload Data
                 */
                /*----------------------------------------------------------*/
                case 'multipart/form-data':
                    return false;
                /*----------------------------------------------------------*/
                /**
                 * Default: Returns Raw Body Data
                 */
                /*----------------------------------------------------------*/
                default:
                    return false;
            }
            /**
             * Return default
             */
            return false;
        }
        /*----------------------------------------------------------*/
        /**
         * Redirects after response sent
         * - TODO: Finish
         */
        /*----------------------------------------------------------*/
        public function redirect(?string $url=null){}
        /*----------------------------------------------------------*/
        /**
         * Send HTTP Response
         * - Employs response->headers->send() to send headers
         * - Sends headers and response body
         */
        /*----------------------------------------------------------*/
        public function send(){
            /**
             * Validate Response headers
             * - Check for status and content type
             * - Send Headers
             * - Send Body
             */
            if($this->headers->has('status') && $this->headers->has('content-type')){
                /**
                 * Send Headers
                 */
                $this->headers->send();
                /**
                 * Send Body
                 */
                echo $this->body;
            } else {
                $this->errors->add('exception', sprintf('Cannot send http response! Missing headers: Status or Content-Type in %s', __FUNCTION__));
                return $this;
            }
        }
    }

?>