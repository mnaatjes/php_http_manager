<?php
    /**
     * Log Errors Function
     * @param String $filePath default FP_ERROR_LOG
     */
    function log_errors($filePath=FP_ERROR_LOG){
        /**
         * Set php.ini Configuration
         */
        ini_set('log_errors', 1);
        ini_set('error_log', $filePath);
        ini_set('error_reporting', E_ALL);
    }
?>