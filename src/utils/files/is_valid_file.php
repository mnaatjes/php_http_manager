<?php
    /**
     * Checks a given filepath and determines if it exists and is a valid file
     * - Validates filepath
     * - Checks file_exists()
     * - Checks is_file()
     */
    function is_valid_file(string $filepath){
        /**
         * Validate filepath
         * - Trim
         * - Check for null
         * - Check for empty
         * - Exists
         */
        if(!is_string($filepath)){
            return false;
        }
        // trim filepath
        $filepath = trim($filepath);
        // check if string empty
        if(empty($filepath)){
            return false;
        }
        // check if exists
        if(!file_exists($filepath)){
            return false;
        }
        // checks if file
        if(!is_file($filepath)){
            return false;
        }
        /**
         * Passed all checks
         * Return true
         */
        return true;
    }
?>