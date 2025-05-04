<?php
    /**
     * Determines the mime type (Content-Type) of a file from a given filepath and returns the mime type
     * @param string $filepath
     * @param bool $strict - Default (false) relies upon extension | True: relies upon strict comparison of all modes
     * @return string File's mime type
     */
    function get_file_mimetype(string $filepath, bool $strict=false){
        /**
         * Declare results array
         */
        $results = [
            'finfo'         => null,
            'mime_content'  => null,
            'from_ext'      => null,
            'shell'         => null
        ];
        /**
         * Attempt 1:
         * - Use finfo_open() to read mimetype
         */
        if(function_exists('finfo_open')){
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if($finfo){
                $results['finfo'] = finfo_file($finfo, $filepath);
                finfo_close($finfo);
            }
        }
        /**
         * Attempt 2:
         * - Use mime_content_type()
         */
        if(function_exists('mime_content_type')){
            $results['mime_content'] = mime_content_type($filepath);
        }
        /**
         * Attempt 3:
         * - using get_file_extension() utils function
         * - compare with EXT_MIME_TYPES array
         */
        $extension           = get_file_extension($filepath);
        $results['from_ext'] = EXT_MIME_TYPES[$extension];
        /**
         * Attempt 4:
         * - using shell_exec()
         */
        if(function_exists('shell_exec')){
            /**
             * Try and catch
             */
            try {
                // Attempt shell_exec()
                $shell_output = trim(
                    shell_exec('file -b --mime-type ' . escapeshellarg($filepath))
                );
                // Evaluate for depreciated mime-types
                if(is_string($shell_output)){
                    if(isset(MIME_TYPES_DEPRECIATED[$shell_output])){
                        // replace depreciated mime-type
                        $results['shell'] = MIME_TYPES_DEPRECIATED[$shell_output];
                    } else {
                        // use shell output
                        $results['shell'] = $shell_output;
                    }
                }
            } catch (Throwable $err) {
                // Could not execute shell
                $results['shell'] = null;
            }
        }
        /**
         * Perform Evaluation
         */
        $unique = array_unique($results);
        $count  = is_array($unique) ? count($unique) : null;
        // Validate count
        if(!is_numeric($count)){
            // Could not determine mime-type
            return null;
        }
        /**
         * Check count
         */
        if($count === 1){
            /**
             * All values the same
             * Return value from unique array
             */
            return $unique[key($unique)];
        } else {
            /**
             * Check if any values are null
             * - Filter out null values
             * - Re-count
             */
            if(in_array(null, $unique, true)){
                // filter null
                $unique = array_filter($unique, function($value){
                    return !is_null($value);
                });
                // re-count
                $count = count($unique);
            }
            /**
             * Check re-count
             */
            if($count === 1){
                /**
                 * All values the same
                 * Return value from unique array
                 */
                return $unique[key($unique)];
            } else {
                /**
                 * Count greater than or less than 1
                 * - Determine strict level; if false continue | true return null
                 * - Evaluate
                 */
                if($strict === false){
                    /**
                     * Not strict-mode:
                     * - Filter array by file extension
                     * - Find unique value count
                     * - Return value on count === 1
                     */
                    $results = array_filter($results, function($value) use($extension){
                        if(strpos($value, $extension)){
                            return true;
                        }
                    });
                    // Find unique
                    $unique = array_unique($results);
                    // Count
                    $count  = count($unique);
                    // Evaluate
                    if(is_numeric($count) && $count === 1){
                        return $unique[key($unique)];
                    } else {
                        // Failure
                        return null;
                    }
                } else {
                    /**
                     * Strict mode:
                     * - Too many unique values
                     * - Return null
                     */
                    return null;
                }
            }
        }
    }

?>