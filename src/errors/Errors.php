<?php
    /*----------------------------------------------------------*/
    /**
     * Errors Object.
     * - Provides consistent object oriented approach for creating errors to report within other frameworks and classes.
     * - Magic Methods, __get(), __set(), __isset(), __unset()
     * - Iterable
     * - Countable
     * 
     * @version 2.0
     * @since 1.0:  Initial version on php7.4
     * @since 2.0   Overview:
     *  - php version 8.3
     *  - Migrated to php_http_manager in self-contained package
     *  - __set() returns void not boolean
     */
    /*----------------------------------------------------------*/
    class Errors  {
        /**
         * Error Properties
         */
        public ?string $logPath     = null;
        public array $config        = [];
        public array $container     = [];
        protected array $codes      = [];
        protected array $categories = [];
        protected array $last_entry = [];
        protected ?array $children  = null;
        /*----------------------------------------------------------*/
        /**
         * Constructor.
         * - Declares $container array
         * - Sets initial $data array properties
         * TODO: Remove __get() and __set() and replace with easier methods get(), set(), etc...
         */
        /*----------------------------------------------------------*/
        public function __construct(?array $config=null){
            /**
             * Validate config
             */
            $this->config = $this->parseConfig($config);
            /**
             * Set logging path
             */
            $this->logPath = $this->config['log_path'];
            /**
             * Set Default Parameters (gettable and settable)
             */
            $this->categories = [
                'argument', 
                'assertion', 
                'base',
                'exception',
                'length',
                'logical',
                'overflow',
                'range',
                'runtime',
                'type'
            ];
            /**
             * Codes generated from Category Keys
             */
            $this->codes = array_keys($this->categories);
        }
        /*----------------------------------------------------------*/
        /**
         * Validation Method: Parses $config parameter and returns array
         * - Sets defaults if not set
         */
        /*----------------------------------------------------------*/
        private function parseConfig(?array $config){
            /**
             * Define Default Error Configuration
             */
            $default = [
                'log_path'  => 'ERRORS_LOG.php',
                'log'       => true,
                'display'   => true,
                'send'      => true
            ];
            /**
             * Check if null
             * - Return all defaults
             * - Parse values if not null
             */
            if(is_null($config)){
                return $default;
            } else {
                return array_merge($default, $config);
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Settings Method: 
         * @param string $name
         * @param string $value
         * @return void
         */
        /*----------------------------------------------------------*/
        private function logError(string $name, string $value=''){
            /**
             * Validate method
             * - True: log to file
             * - False: return void
             */
            if($this->config['log'] === true){
                // Log Error
                // TODO: Recreate logging method
                log_dump(sprintf('ERROR: %s', ucfirst($name)), $value, $this->logPath);
                return;
            }
            // Leave method
            return;
        }
        /*----------------------------------------------------------*/
        /**
         * Settings Method: 
         * @param string $name
         * @param string $value
         * @return void
         */
        /*----------------------------------------------------------*/
        private function displayError(string $name, string $value=''){
            /**
             * Validate method
             * - True: display error
             * - False: return void
             */
            if($this->config['display'] === true){
                // Check error settings from php.ini
                // Display Error
                trigger_error(sprintf('%s: %s', ucfirst($name), $value));
                return;
            }
            // Leave method
            return;
        }
        /*----------------------------------------------------------*/
        /**
         * Magic Method: Returns property from $container
         * @param string $name Property Name within $container
         * @return void - Gets $container value
         */
        /*----------------------------------------------------------*/
        private function __get(string $name){
            /**
             * Validate
             * - Normalize
             * - Check isset()
             */
            $name = $this->normalizeProperty($name);
            if(isset($this->container[$name])){
                return $this->container[$name];
            } else {
                return null;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Magic Method: 
         * @param string $name Property Name within $container
         * @param mixed $value Value to set
         * @return bool - True is successful
         */
        /*----------------------------------------------------------*/
        private function __set(string $name, $value): void{
            /**
             * Validate:
             * - Normalize $name
             * - Check for null
             * - Validate from $categories
             */
            $name = $this->normalizeProperty($name);
            if(is_null($name)){
                return;
            }
            if(!in_array($name, $this->categories)){
                // Invalid Category
                return;
            }
            /**
             * Set error:
             * - Check isset()
             * - Check is array
             * - Append to container: each element is an array
             */
            if(isset($this->container[$name]) && is_array($this->container[$name])){
                $this->container[$name] = array_merge((array) $this->container[$name], (array) $value);
            } else {
                $this->container[$name] = [];
                $this->container[$name] = [$this->container[$name], ... (array) $value];
            }
            /**
             * Log Error
             */
            $this->logError($name, $value);
            /**
             * Display Error
             */
            $this->displayError($name, $value);
            /**
             * Set Last Entry
             */
            $this->last_entry = [$name => $value];
        }
        /*----------------------------------------------------------*/
        /**
         * Magic Method: Checks if a property is set
         * - Checks for normalized $name value
         * @return bool - True if set
         */
        /*----------------------------------------------------------*/
        private function __isset(string $name): bool{
            /**
             * Validate and normalize $name
             */
            $name = $this->normalizeProperty($name);
            if(is_null($name)){
                return false;
            }
            /**
             * Return isset()
             */
            return isset($this->container[$name]);
        }
        /*----------------------------------------------------------*/
        /**
         * Private Method: Normalizes the property name
         * @param string $name
         * @return string|null Normalized String | Null on failure
         */
        /*----------------------------------------------------------*/
        private function normalizeProperty(string $name): ?string{
            // Trim String
            $name = trim($name);
            // Check for empty string
            if(empty($name)){
                return null;
            }
            // Return Normalized property name
            return strtolower($name);
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Checks if a property exists in $container.
         * - Uses __isset()
         * @return bool
         */
        /*----------------------------------------------------------*/
        public function has(string $name): bool{
            // Normalize
            $name = $this->normalizeProperty($name);
            // Check if exists
            return isset($name);
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Adds Error based on Error Category
         * - Appends to existing array
         * @param string $category
         * @param string|null $value
         * @return bool True if successful | False if failure
         */
        /*----------------------------------------------------------*/
        public function add(string $name, ?string $value): bool{
            /**
             * Validate $name
             */
            if(empty(trim($name))){
                // Name empty and cannot add
                return false;
            }
            /**
             * Use __set()
             */
            $this->$name = $value;
            return true;
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Purges all values within category sub-array
         * @param string $name Error Category array of $container data array
         * @return bool True on success
         */
        /*----------------------------------------------------------*/
        public function purgeCategory(string $name): bool{
            /**
             * Validate Category
             */
            $name = $this->normalizeProperty($name);
            if(isset($this->container[$name])){
                // Clear contents
                $this->container[$name] = [];
                // Return empty
                return empty($this->container[$name]);
            }
            /**
             * Return default
             */
            return false;
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Get all errors
         * @return array $container contents
         */
        /*----------------------------------------------------------*/
        public function getAll(){
            return $this->container;
        }
        /*----------------------------------------------------------*/
        /**
         * Utility Method: Returns last (most recent) error
         */
        /*----------------------------------------------------------*/
        public function getLast(){
            return $this->last_entry;
        }
        /*----------------------------------------------------------*/
        /**
         * Countable Method: Counts elements of object
         * @return int Count of $this->properties
         */
        /*----------------------------------------------------------*/
        public function count(): int{
            // Set initial count
            $count = 0;
            foreach($this->container as $_ => $arr){
                // Iterative count of category values
                $count += count($arr);
            }
            return $count;
        }
    }
?>