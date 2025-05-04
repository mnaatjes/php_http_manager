<?php
    /*----------------------------------------------------------*/
    /**
     * Sanitizes, Filters, and Validates the $_SERVER superglobal:
     * - Validates array existence
     * - Filters and sanitizes values by datatype
     * @param array|null $config Configuration array containing valid parameters; e.g. valid_methods => ['GET', 'POST'...]
     * @return array|null Array of $_SERVER properties
     */
    /*----------------------------------------------------------*/
    function sanitize_server_data(?array $config=null): ?array{
        /**
         * Validate existence of $_SERVER superglobal and length
         */
        if(!isset($_SERVER)){
            return null;
        }
        if(empty($_SERVER)){
            return null;
        }
        if(!is_array($_SERVER)){
            return null;
        }
        /**
         * @var array $results Array to contain parsed $_SERVER properties
         */
        $results = [];
        /**
         * Loop $_SERVER properties
         * - Sanitize and Validate
         * - Format keys and values
         * - Output array of [$key, $val] and push to $results
         */
        foreach($_SERVER as $key => $value){
            /**
             * Sanitize
             */
            $sanitized = sanitize_server_property($key, $value, $config);
            /**
             * Merge sanitized and formatted key, value array with $results
             */
            $results = array_merge($results, $sanitized);
        }
        /**
         * Validate results
         * Return Default
         */
        if(empty($results)){
            return null;
        }
        return $results;
    }
    /*----------------------------------------------------------*/
    /**
     * Sanitize $_SERVER superglobal key and value.
     * - Filters and validates $_SERVER property by property name
     * - Employs sanitize_data methods
     * @param string $prop_name Key name of $_SERVER property
     * @param string $value Value string of $_SERVER property
     * @param array|null $config Configuration array containing valid parameters; e.g. valid_methods => ['GET', 'POST'...]
     * @return array|null [$prop => $value], Null on failure
     */
    /*----------------------------------------------------------*/
    function sanitize_server_property(string $prop_name, string $value, ?array $config=null): ?array{
        /**
         * Validate Non-Strings
         */
        if(!is_string($value)){
            switch(strtoupper($prop_name)){
            /*----------------------------------------------------------*/
            /**
             * Arguments Properties:
             * - Check $config if allowed
             */
            /*----------------------------------------------------------*/
            case 'ARGV':
            case 'ARGC':
                /**
                 * Set default / config values for validation
                 */
                $allowed    = isset($config['arguments']) ? false : true;
                $arg_length = !isset($config['arguments']['length']) ? 2 : $config['arguments']['length'];
                /**
                 * Abort if not allowed
                 */
                if($allowed === false){
                    return [$prop_name => null];
                } else if ($allowed === true){
                    /**
                     * ARGS Allowed:
                     * - Check Array
                     * - Check Int
                     */
                    if($prop_name === 'ARGV' && !is_array($value)){
                        // Invalid Data Type
                        return [$prop_name => null];
                    }
                    /**
                     * Validate ARGC Int Value
                     */
                    if($prop_name === 'ARGC'){
                        // Check data-type
                        if(!is_numeric($value)){
                            // Invalid Data Type
                            return [$prop_name => null];
                        } else {
                            // Cast as int
                            $args = is_int($value) ? $value : intval($value);
                            if($args !== $arg_length){
                                // Invalid length
                                return [$prop_name => null];
                            } else if ($args === $arg_length){
                                // Valid ARGC
                                return [$prop_name => $value];
                            }
                        }
                    }
                }
            /*----------------------------------------------------------*/
            /**
             * Default (Non-String)
             */
            /*----------------------------------------------------------*/
            default: 
                return [$prop_name => null];
            }
        }
        /**
         * Validate Strings
         * Switch property based on $prop name
         */
        switch(strtoupper($prop_name)){
            /*----------------------------------------------------------*/
            /**
             * Filepath Properties
             * - Filter and Sanitize
             * - Implement preg-match
             */
            /*----------------------------------------------------------*/
            case 'PHP_SELF':
            case 'DOCUMENT_ROOT':
            case 'SCRIPT_NAME':
            case 'PATH_INFO':
            case 'PATH_TRANSLATED':
                // Trim value and Strip Tags
                $value      = strip_tags(trim($value));
                $filtered   = filter_var($value, FILTER_VALIDATE_URL);
                $sanitized  = filter_var($value, FILTER_SANITIZE_URL);
                /**
                 * Check for filepath characteristics
                 */
                if(!preg_match('/^[a-zA-Z0-9\/._-]+$/', $value)){
                    // Not a valid filepath
                    return [$prop_name => null];
                }
                /**
                 * Check filepaths: PHP_SELF, SCRIPT_NAME
                 * - Has extension
                 */
                if($prop_name === 'SCRIPT_NAME' || $prop_name === 'PHP_SELF'){
                    if(!is_int(strpos($value, '.'))){
                        // Return as null - Missing file extension
                        return [$prop_name => null];
                    }
                }
                /**
                 * Return sanitized value
                 */
                return [$prop_name => $sanitized];
            /*----------------------------------------------------------*/
            /**
             * URL Properties:
             * - Run URL check
             * - On fail, check if filepath
             */
            /*----------------------------------------------------------*/
            case 'HTTP_REFERER':
            case 'REQUEST_URI':
            case 'REDIRECT_URL':
            case 'REDIRECT_QUERY_STRING':
                // Filter Property as URL
                $filtered = filter_var($value, FILTER_SANITIZE_URL);
                // Check if Filtered returned false
                if($filtered === false){
                    // Perform preg-match for filepath characters
                    if(!preg_match('/^[a-zA-Z0-9\/._-]+$/', $value)){
                        // Failed both tests
                        return [$prop_name => null];
                    } else {
                        // Sanitize URL/Path
                        $sanitized = filter_var($value, FILTER_SANITIZE_URL);
                        // Passed preg-match
                        return [$prop_name => $sanitized === false ? null : $sanitized];
                    }
                } else {
                    // Sanitize
                    $sanitized = filter_var($filtered, FILTER_SANITIZE_URL);
                    // Passed filter
                    return [$prop_name => $sanitized === false ? null : $sanitized];
                }
            /*----------------------------------------------------------*/
            /**
             * Domains: SERVER_NAME, HTTP_HOST, 
             * - Check URL
             * - Check Domain Name
             * - Check config set
             * - Check IP
             */
            /*----------------------------------------------------------*/
            case 'SERVER_NAME':
            case 'HTTP_HOST':
                // Trim
                $value = trim($value);
                if(empty($value)){
                    // MUST be a populated string!
                    return [$prop_name => null];
                }
                /**
                 * Set config values
                 */
                $valid_domains      = !isset($config['domains']) ? null : $config['domains'];
                $max_length_domain  = !isset($config['max_length_domain']) ? 255 : $config['max_length_domain'];
                // Check domain against array
                if(is_array($valid_domains) && !in_array($value, $valid_domains)){
                    // Domain is not valid
                    return [$prop_name => null];
                }
                // Check max length of value
                if(strlen($value) > $max_length_domain){
                    // Server Name improbably long
                    return [$prop_name => null];
                }
                /**
                 * Filter Domain
                 * Return server name
                 */
                $filtered = filter_var($value, FILTER_VALIDATE_DOMAIN);
                return [$prop_name => $filtered === false ? null : $filtered];
            /*----------------------------------------------------------*/
            /**
             * Protocol:
             * - Check $config
             * - Check against array
             */
            /*----------------------------------------------------------*/
            case 'SERVER_PROTOCOL': 
                $valid_protocols = !isset($config['protocols']) ?  VALID_HTTP_PROTOCOLS : $config['protocols'];
                if(!in_array(strtoupper($value), $valid_protocols)){
                    return [$prop_name => null];
                } else {
                    return [$prop_name => $value];
                }
            /*----------------------------------------------------------*/
            /**
             * Request Method:
             * - Check for config
             * - Check in array
             */
            /*----------------------------------------------------------*/
            case 'REQUEST_METHOD':
                // Check valid methods
                $valid_methods = !isset($config['methods']) ? VALID_HTTP_METHODS : $config['methods'];
                // Run check
                if(!in_array(strtoupper($value), $valid_methods)){
                    return [$prop_name => null];
                } else {
                    return [$prop_name => $value];
                }
            /*----------------------------------------------------------*/
            /**
             * IP Addresses:
             */
            /*----------------------------------------------------------*/
            case 'SERVER_ADDR':
            case 'REMOTE_ADDR':
                // Filter IP
                $value = filter_var($value, FILTER_VALIDATE_IP);
                // Test filter
                if($value === false){
                    return [$prop_name => null];
                } else {
                    return [$prop_name => $value];
                }
            /*----------------------------------------------------------*/
            /**
             * Time Properties:
             * - Set time tolerance
             * - Cast to int
             * - Validate range
             */
            /*----------------------------------------------------------*/
            case 'REQUEST_TIME':
            case 'REQUEST_TIME_FLOAT':
                // Set tolerance
                $tolerance  = !isset($config['tolerance']) ? 60 : $config['tolerance'];
                // Filter int value
                $filtered  = false;
                if($prop_name === 'REQUEST_TIME'){
                    $filtered = filter_var($value, FILTER_VALIDATE_INT);
                } else if($prop_name === 'REQUEST_TIME_FLOAT'){
                    $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
                }
                // Check for tolerance
                if(($filtered < $filtered - $tolerance) || ($filtered > $filtered + $tolerance)){
                    return [$prop_name => null];
                } else {
                    return [$prop_name => $filtered];
                }
            /*----------------------------------------------------------*/
            /**
             * Numeric Properties:
             * - Check if numeric
             * - Cast as int
             */
            /*----------------------------------------------------------*/
            case 'SERVER_PORT':
            case 'REMOTE_PORT':
            case 'CONTENT_LENGTH':
            case 'REDIRECT_STATUS':
                /**
                 * Validate Numeric
                 * Cast as int
                 * Check ranges
                 */
                if(!is_numeric($value)){
                    return [$prop_name => null];
                }
                /**
                 * Validate Ports:
                 * - Check for config value
                 * - Compare
                 */
                if($prop_name === 'SERVER_PORT' || $prop_name === 'REMOTE_PORT'){
                    $range = !isset($config['ports']) 
                            ? ['options' => ['min_range' => 1, 'max_range' => 65535]]
                            : ['options' => [
                                'min_range' => $config['ports']['min_range'],
                                'max_range' => $config['ports']['max_range']
                            ]];
                    $value = filter_var($value, FILTER_VALIDATE_INT, $range);
                    $value = $value === false ? null : $value;
                    return [$prop_name => $value];
                }
                /**
                 * Validate Content Length
                 * - Check for config value
                 * - Default: 10MB in bytes
                 * - Compare
                 */
                if($prop_name === 'CONTENT_LENGTH'){
                    $max_length_content  = !isset($config['max_length_content'])? (10 * 1024 * 1024) : $config['max_length_content'];
                    $value               = filter_var($value, FILTER_VALIDATE_INT);
                    if($value > $max_length_content){
                        return [$prop_name => null];
                    } else {
                        return [$prop_name => $value];
                    }
                }
                /**
                 * Validate status code
                 * - Cast to int
                 * - Check for $config setting
                 * - Check against VALID_HTTP_STATUS or Config
                 */
                if($prop_name === 'REDIRECT_STATUS'){
                    $value          = intval($value);
                    $valid_status   = !isset($config['status']) ? VALID_HTTP_STATUS : $config['status'];
                    if(!array_key_exists($value, $valid_status)){
                        return [$prop_name => null];
                    } else {
                        // Is valid status
                        return [$prop_name => $value];
                    }
                }
                /**
                 * Default
                 */
                return [$prop_name => null];
            /*----------------------------------------------------------*/
            /**
             * Default(String):
             * - Call sanitize_string() function
             */
            /*----------------------------------------------------------*/
            default: 
                /**
                 * Default string sanitation parameters (non-destructive):
                 * - Trim value
                 * - Set max length of string
                 */
                $value      = trim($value);
                $max_length = !isset($config['max_length'])? 2048 : $config['max_length'];
                if(strlen($value) > $max_length){
                    $value = null;
                }
                return [$prop_name => $value];
        }
        /**
         * Return default
         */
        return [$prop_name => null];
    }
?>