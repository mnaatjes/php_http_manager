<?php
    /**
     * Finds a $needle substring and alters / replaces it.
     * - Find needle (Case Sensitive)
     * - Ability to modify case
     * - Ability to substitute different substring
     * - Returns modified string
     * @param string $haystack
     * @param string $needle
     * @param int|null $case
     * @param int|null $substitute
     */
    function str_replace_substr(string $haystack, string $needle, ?int $case=null, ?string $substitute=null): string{
        /**
         * Validate string
         */
        if(empty(trim($haystack)) || empty(trim($needle))){
            // Nothing to change
            return $haystack;
        }
        /**
         * Validate string position of needle
         */
        $position = strpos($haystack, $needle);
        if($position === false){
            return $haystack;
        }
        /**
         * Find string coordinates
         */
        $start  = substr($haystack, 0, $position);
        $substr = substr($haystack, $position, strlen($needle));
        $end    = substr($haystack, $position + strlen($needle));
        /**
         * Determine substitution
         */
        $replacement = is_null($substitute) ? $substr : $substitute;
        /**
         * Check for case modifications
         */
        if(!is_null($case) && is_int($case)){
            // Normalize String
            $result = strtolower(trim($replacement));
            if($case === CASE_UPPER){
                $result = strtoupper(trim($replacement));
            } else if ($case === CASE_CAPS){
                $result = ucfirst($replacement);
            } else {
                // Default
                $result = $replacement;
            }
        }
        /**
         * Return recombined string
         */
        return $start . $result . $end;
    }
?>