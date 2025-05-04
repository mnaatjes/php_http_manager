<?php
    /**
     * Checks for a provided delimiter and returns true
     * - Case Sensitive
     * - Does not trim string
     * @param string $str String to check
     * @param string $delimiter Character to evaluate; Default "_"
     * @return bool True if present
     */
    function str_has_delimiter(string $str, string $delimiter='_'): bool{
        /**
         * Validate string
         */
        if(!is_string($str)){
            return false;
        }
        return strpos($str, $delimiter) !== false;
    }

?>