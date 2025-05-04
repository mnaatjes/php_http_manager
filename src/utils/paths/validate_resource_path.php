<?php
    /**
     * Validates resource path
     * 
     * @param string $path
     * 
     * @example '/' = valid
     * @example '/products' = valid
     * @example '/products/users' = valid
     * @example '/products/us*(&^*(&ers' = invalid
     * 
     * @return bool
     */
    function validate_resource_path($path): bool{
        /**
         * Declare patterns and properties
         * - Trim and remove whitespace
         */
        $path = str_replace(' ', '', trim($path));
        // Check if empty
        if(empty($path)){
            return false;
        }
        /**
         * Check if URL encoded
         * - Decode if necessary
         */
        $url_encoded = (bool) preg_match('/%[0-9a-fA-F]{2}/', $path);
        if($url_encoded){
            $path = urldecode($path);
        }
        /**
         * Check for leading '/' forward slash
         */
        if(!str_starts_with($path, '/')){
            // Failed check
            return false;
        }
        // Check if root path
        if($path === '/'){
            return true;
        }
        // Count number of "/"
        $count      = substr_count($path, '/');
        // Set and test pattern
        $pattern    = '/^\/[a-zA-Z0-9_{}-]+$/';
        $match      = preg_match($pattern, $path) === 1;
        /**
         * Explode multi-path
         */
        if($count > 1 && $match === false){
            // Check parts for validity
            foreach(explode('/', $path) as $id){
                // skip empty
                $id = trim($id);
                if(empty($id)){
                    continue;
                }
                $id = '/' . $id;
                $id_match = preg_match($pattern, $id) === 1;
                // On failure, return
                if($id_match === false){
                    return false;
                }
            }
            /**
             * Loop resolved to true
             */
            return true;
        } else {
            /**
             * Return pattern match boolean
             */
            return $match;
        }
    }
?>