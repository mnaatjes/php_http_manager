<?php
    /*----------------------------------------------------------*/
    /**
     * utils__strings.php
     * 
     * This file contains references to utils__strings files
     * 
     * @package UtilsStrings
     * @author Michael Naatjes <michael.naatjes87@gmail.com>
     * @version 1.0.0
     * @since 02-07-2025
     */
    /*----------------------------------------------------------*/
    /**
     * Parses supplied file size from php.ini (e.g. 8M) and returns an int value in bytes
     * @param string $str - php.ini memory value string
     * 
     * @return int - Value of php.ini memory value in bytes(int)
     */
    /*----------------------------------------------------------*/
    function ini_parse_byte_str($str){
        $units = ['B', 'K', 'M', 'G', 'T', 'P'];
        $num = '';
        $unit = '';
        // extract values
        array_map(function($ele) use (&$num, &$unit){
            if(is_numeric($ele)){
                $num .= $ele;
            } else {
                $unit .= $ele;
            }
        }, str_split($str));
        // alter data-type to int
        $num = intval($num);
        // search array for matching unit
        $search = array_search(strtoupper($unit), $units);
        // validate
        if(!$search){
            return false;
        }
        /**
         * Return size in bits
         */
        return $num * pow(1024, $search);
    }

?>