<?php
    /**
     * Replacement for str_ends_with() for php versions prior to 8.0
     * - Searches for the $needle substring from the end(-1) of the $haystack
     * - Uses strlen() and substr() to evaluate
     * - Case sensitive!
     * @param string $haystack
     * @param string $needle
     * @return bool True is found
     */
    function str_ends_with(string $haystack, string $needle): bool{
        /**
         * Get length of needle
         */
        $length = strlen($needle);
        // Check for empty $needle
        if($length === 0){
            return true;
        }
        // Evaluate using substr()
        return substr($haystack, -$length) === $needle;
    }
?>