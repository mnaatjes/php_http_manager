<?php
    /**
     * Replacement for str_starts_with() for php versions prior to 8.0
     * - Searches for the $needle substring from the start(0) of the $haystack
     * - Uses strcmp()
     * - Case sensitive!
     * @param string $haystack
     * @param string $needle
     * @return bool True is found
     */
    function str_starts_with(string $haystack, string $needle): bool{
        /**
         * Use strcmp() to evaluate:
         * - Binary search of n-characters
         */
        if(strncmp($haystack, $needle, strlen($needle)) === 0){
            return true;
        }
        return false;
    }
?>