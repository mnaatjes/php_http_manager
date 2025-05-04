<?php
    /**
     * Determine if the provided filepath resolves into a valid "text/csv" mime type
     * 
     * @param string $filepath - file path to evaluate
     * @return bool - True === "text/csv"
     */
    function is_file_csv(string $filepath){
        /**
         * Check that file exists
         */
        if(!file_exists($filepath)|| !is_readable($filepath)){
            triger_error('File either does not exist or cannot be read!');
            return false;
        }
        /**
         * Validate file
         * Open file
         * Read first line
         * Close resource
         * Validate first line
         */
        $file = fopen($filepath, 'r');
        if(!$file){
            trigger_error('Unable to open file!');
            return false;
        }
        $line = fgets($file);
        fclose($file);
        if(!$line){
            // file empty
            return false;
        }
        /**
         * Use pregmatch_all() to test for characteristics
         * @var string $pattern - Fields: Comma seperation, double-quotes
         *                      - Allows: Double-quotes, whitespace around commas, varying field numbers
         *                      - Considers single line input
         *                      - Handles empty lines
         */
        $pattern    = '/(?:^|[\r\n]+)(?:"([^"]*(?:""[^"]*)*)"|([^,"\r\n]*))(?:,|\r?\n|$)/';
        $preg_test  = preg_match_all($pattern, $line, $matches, PREG_SET_ORDER);
        /**
         * Check first line for delimiters
         * Determine the number of occurances of the delimiters
         */
        $needles    = [',', ';', '\t', '|', '^'];
        $positions  = strpos_from_array($needles, $line);
        $count      = array_reduce(array_keys($positions), function($acc, $needle) use($positions, $line){
            // Declare init props
            $pos = $positions[$needle];
            // Check for int value
            if(is_int($pos)){
                // test occurances of needle
                $test = substr_count($line, $needle);
                if($test > $acc){
                    // save as max value
                    $acc = $test;
                }
            }
            return $acc;
        });
        if(!is_null($count) && $count > 1){
            // Evaluate preg_match() results
            if(is_int($preg_test) && count($matches) > 0){
                // Found delimiter
                return true;
            }
            trigger_error('Failed Pregmatch Test on is_string_csv()');
            return true;
        }
        // Could not fine any of the delimiters
        return false;
    }
?>