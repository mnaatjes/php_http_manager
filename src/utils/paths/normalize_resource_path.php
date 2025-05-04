<?php
    /**
     * Normalizes a rest api / request_uri path
     * @param string $path
     * @return string|null normalized and validated path | Null on failure
     */
    function normalize_resource_path(string $path): ?string{
        /**
         * Validate
         */
        if(!is_string($path)){
            return null;
        }
        /**
         * Trim and remove whitespace
         * - Check if empty
         */
        $path = str_replace(' ', '', trim($path));
        if(empty($path)){
            return null;
        }
        /**
         * Check if url encoded and decode
         */
        $url_encoded = (bool) preg_match('/%[0-9a-fA-F]{2}/', $path);
        if($url_encoded){
            $path = urldecode($path);
        }
        /**
         * Check for leading "/"
         */
        if(!str_starts_with($path, '/')){
            $path = '/' . $path;
        }
        /**
         * Remove double slashes
         */
        $path = str_replace('//', '/', $path);
        /**
         * TODO: Avoid trailing "/"
         * TODO: What about when a query is involved?
         */
        /**
         * Format and return
         */
        return strtolower($path);
    }

?>