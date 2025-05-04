<?php
    /*----------------------------------------------------------*/
    /**
     * Enables E_ALL errors by via ini_set()
     *
     * @param void
     * @return void - Sets "display_errors" == 1; "display_startup_errors" == 1 and E_ALL error reporting
     */
    /*----------------------------------------------------------*/
    function ini_errors_enable(){
        ini_set('display_errors', 1); 
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL); 
    }
?>