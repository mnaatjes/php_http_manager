<?php
    /**
     * Extracts the file extension from a file
     * @param string $filepath
     * @return string|null file extension (without leading period)
     */
    function get_file_extension(string $filepath): ?string{
        /**
         * Declare results array
         */
        $results = [
            'pathinfo' => null,
            'from_string' => null
        ];
        /**
         * Attempt 1:
         * - Use pathinfo() to read extension
         */
        $results['pathinfo'] = pathinfo($filepath, PATHINFO_EXTENSION);
        /**
         * Attempt 2: From filepath string
         * - Evaluate the filepath string
         * - get extension from last period
         */
        $results['from_string'] = substr($filepath, strrpos($filepath, '.') + 1);
        /**
         * Search for single unique value:
         * - Validate unique result
         * - Count unique
         * - Extract value
         */
        $unique = array_unique($results);
        if(!is_array($unique)){
            // failed: function failed
            return null;
        }
        if(count($unique) > 1){
            // failed: multiple values
            return null;
        }
        // Validate string
        $ext = $unique[key($unique)];
        if(is_string($ext)){
            /**
             * Success:
             * - Results are consistent
             * - Result is a string
             * - Return value
             */
            return $ext;
        } else {
            /**
             * Failure: Return default
             */
            return null;
        }
    }
?>