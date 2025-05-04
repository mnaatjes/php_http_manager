<?php
    /**
     * Uses DIRECTORY_SEPARATOR to replace a filepath or directory string with the OS compliant character.
     * 
     * @param string $path - Path to evaluate and alter if necessary
     * @return string|null - Altered(if necessary) path to correct separator; i.e. normalized; Null for invalid
     */
    function normalize_path(?string $path): ?string{
        /**
         * Trim any leaning or trailing whitespaces
         */
        $path = trim($path);
        /**
         * Check for empty path
         */
        if(empty($path)){
            trigger_error('Cannot normalize path! Path: ' . $path . ' is empty!');
            return null;
        }
        /**
         * Replace both forward and backward slashes with $sep
         */
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        /**
         * Check length of filepath:
         * - If the path is only one character long: ensure it is a directory separator
         */
        if(strlen($path) === 1 && $path !== DIRECTORY_SEPARATOR){
            trigger_error(sprintf('Path parameter: %s too short and missing directory separator!', $path));
            // Invalid filepath
            return null;
        }
        /**
         * Remove redundant separators:
         * - Replace occurrences of 2 or more consecutive separator characters
         * - preg_quote() escapes special regex characters
         * - {2,} two or more occurrences
         * Remove excessive periods for relative paths
         */
        $path = preg_replace('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '{2,}/', DIRECTORY_SEPARATOR, $path);
        $path = preg_replace('/\.{3,}/', '..', $path);
        /**
         * Check if path contains absolute path characteristics
         * Check for relative paths and root directory
         * - Check first character of path
         * - Check last character of path
         */
        $leading    = str_starts_with($path, DIRECTORY_SEPARATOR);
        $trailing   = str_ends_with($path, DIRECTORY_SEPARATOR);
        /**
         * Explode segments of filepath:
         * - Remove '.' and empty segments
         * - Remove inner relative path traversals and go up a level
         * - Return relative as $results array
         */
        $results  = [];
        $segments = explode(DIRECTORY_SEPARATOR, $path);
        foreach($segments as $segment){
            // remove . and empty
            if($segment === '.' || $segment === ''){
                continue;
            } else if($segment === '..'){
                // Check if $results array has any segments
                if(!empty($results)){
                    // check previous segment in results
                    if(end($results) === '..'){
                        // append to relative traversal
                        $results[] = '..';
                    } else {
                        // go up a level by removing the previous segment
                        array_pop($results);
                    }
                } else {
                    // place at start of path
                    $results[] = '..';
                }
            } else {
                // append segment
                $results[] = $segment;
            }
        }
        $normal_path = implode(DIRECTORY_SEPARATOR, $results);
        /**
         * Prepend and/or append Directory Separators if present before loop
         */
        if($leading && !str_starts_with($normal_path, DIRECTORY_SEPARATOR)){
            $normal_path = DIRECTORY_SEPARATOR . $normal_path;
        }
        if($trailing && !str_ends_with($normal_path, DIRECTORY_SEPARATOR)){
            $normal_path = $normal_path . DIRECTORY_SEPARATOR;
        }
        /**
         * Return Normalized Path
         */
        return $normal_path;
    }
?>