<?php
    /**
     * Method to include all files within supplied directory path.
     * - Uses require_once() on all files within a given directory
     * - Grabs all files within directory and validates not empty
     * - Normalizes and uses Absolute Paths
     * - Returns true if successful or false if failed validation
     * @param string $path Path to require_once() files from
     * @param array $extensions Array of file extensions to include, Default = [.php]
     * @return bool True = success, False on failure
     */
    function require_once_dir(?string $path=null, array $extensions=['.php']): bool{
        // Validate $path is populated
        if(empty($path) || is_null($path)){
            return false;
        }
        /**
         * Collect Contents array from scandir_recursive()
         * - Include $extensions array
         * - Validate $contents
         */
        $contents = scandir_recursive($path, ['ext' => $extensions]);
        if(!is_array($contents)){
            // Returned null; No contents found
            return false;
        } else if(empty($contents)){
            // Returned []; No contents found
            return false;
        }
        /**
         * Loop $contents and require_once()
         * - Validate filepath
         * - Execute require_once();
         */
        foreach($contents as $item){
            // Validate
            if(is_file($item)){
                require_once($item);
            }
        }
        /**
         * Reached the end of script
         * Return true
         */
        return true;
    }
?>