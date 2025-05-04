<?php
    /**
     * Converts a CamelCase string into a Kebab-Case.
     * - Ability to change delimiter
     * - Can capitalize, uppercase, lowercase
     * @param string $str String to change
     * @param int $case Integer value for case; Default CASE_LOWER
     * @param string $delimiter Character to divide words; Default = "_"
     * @return string
     */
    function str_camel_to_kebab(string $str, int $case=CASE_LOWER, string $delimiter='-'): string{
        /**
         * Validate string
         */
        if(!is_string($str)){
            return '';
        }
        $str = trim($str);
        if(empty($str)){
            return '';
        }
        /**
         * Use preg_split and explode string at capital letters
         * - Check for empty elements: skip
         * - Assign $case
         * - Implode with delimiter
         */
        $str_arr    = preg_split('/(?=[A-Z])/', $str);
        $result_arr = [];
        foreach($str_arr as $ele){
            // Normalize String
            $ele = strtolower(trim($ele));
            // Skip empty
            if(empty($ele)){
                continue;
            } else {
                if($case === CASE_LOWER){
                    $ele = strtolower($ele);
                } else if($case === CASE_UPPER){
                    // Select case
                    $ele = strtoupper($ele);
                } else if ($case === CASE_CAPS){
                    $ele = ucfirst($ele);
                }
                // Else: no change
            }
            // Push to results array
            $result_arr[] = $ele;
        }
        /**
         * Implode with delimiter
         * Return formatted string
         */
        $imploded = implode($delimiter, $result_arr);
        /**
         * Remove redundant $delimiters
         * - Escape redundant delimiters
         * - Replace consecutive occurrences with single delimiter
         */
        $escape = preg_quote($delimiter, '/');
        return preg_replace('/' . $escape . '+/', $delimiter, $imploded);
    }
?>