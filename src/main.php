<?php
    /**
     * HTTP Controller Primary file to include to use package
     * @package httpController
     * @author Michael Naatjes
     * @license MIT
     * @version 2.0
     * 
     * @since 1.0   Initial Package
     * @since 2.0   Overview:
     *  - Consolidated entire package into own repository
     */
    /**
     * CONSTANTS
     */
    require_once('constants/valid_http_methods.php');
    require_once('constants/valid_http_protocols.php');
    require_once('constants/valid_http_status.php');
    /**
     * UTILITIES
     */
    require_once('utils/utils_main.php');
    /**
     * ERRORS OBJECT
     * - Error logging object
     * TODO: Rename Errors Object HTTPErrors
     */
    require_once('errors/Errors.php');
    /**
     * HTTP OBJECTS
     */
    require_once('http/Headers.php');
    require_once('http/URIInterface.php');
    require_once('http/Request.php');
    require_once('http/Response.php');
    /**
     * Router object
     */
    require_once('router/Router.php');
?>