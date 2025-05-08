<?php
    /*----------------------------------------------------------*/
    /**
     * Loops through an array and tests a condition against all elements:
     * - If all tests resolve to true, returns true
     * - Returns false if not all tests resolve to true
     * 
     * @since 1.0:  Init
     * @since 2.0: Added to Http Manager Package
     *
     * @param array $arr - 
     * @param callable $callback - 
     * 
     * @property int|string $key - Key for callback
     * @property mixed $val - Value of element for callback
     * @property int $index - Index of element for callback
     * 
     * @return bool - True if conditions all met | False if any condition fails
     * @throws TypeError - Argument $arr is not an array
     */
    /*----------------------------------------------------------*/
    function array_every(array $arr, callable $callback){
        /**
         * Validate Array
         */
        if(!is_array($arr) || count($arr) === 0){
            throw new TypeError('Argument supplied $arr is NOT an array or is empty!');
        }
        /**
         * Perform Loop with callback
         */
        $index  = 0;
        foreach($arr as $key=>$val){
            if(!$callback($key, $val, $index)){
                return false;
            }
            $index++;
        }
        /**
         * All conditions successful
         * Return True
         */
        return true;
    }
?>