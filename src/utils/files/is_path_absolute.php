<?php
    /**
     * Checks against realpath() and DOCUMENT_ROOT to see if the provided path is an absolute path.
     * - Normalizes path
     * - Checks for leading directory separator
     * - Checks for relative path characters
     * - Checks for leading alphabetical drive letter
     * - Does NOT resolve the $path with realpath() or against DOCUMENT_ROOT
     * 
     * @param ?string $path Path to evaluate
     * @return bool True | False
     */
    function is_absolute_path(?string $path=null): bool{
        /**
         * Validate path
         */
        if(is_null($path) || empty($path)){
            trigger_error('Path parameter is empty!');
            return false;
        }
        /**
         * Normalize path
         */
        $path = file_normalize_path($path);
        /**
         * Checks for leading relative path characters "../"
         */
        if(str_starts_with($path, '../')){
            return false;
        }
        /**
         * Check for leading Directory Separator
         * - Windows
         * - Unix
         */
        if(!str_starts_with($path, DIRECTORY_SEPARATOR)){
            /**
             * Check for Windows absolute paths (drive letter / network)
             * - Check for colon
             * - Check for alphabetic drive letter: ctype_alpha()
             */
            if(strpos($path, ':') === 1 && ctype_alpha($path[0])){
                // Windows Drive letter leading absolute path
                return true;
            } else if(str_starts_with($path, '\\\\')){
                // Windows Drive address starts with double-backslash
                return true;
            }
        } else {
            /**
             * Check for UNIX absolute paths
             */
            if(DIRECTORY_SEPARATOR === '/'){
                // UNIX absolute path
                return true;
            } else {
                /**
                 * Return Default with error
                 */
                trigger_error(sprintf('Could not resolve filepath with DIRECTORY SEPARATOR: %s', DIRECTORY_SEPARATOR));
                return false;
            }
        }
        /**
         * Return default
         */
        return false;
    }
?>