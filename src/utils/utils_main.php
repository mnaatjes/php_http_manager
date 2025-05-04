<?php
    /**
     * Utilities Directory Main file
     * 
     * @package PHP HTTP Manager / Utils
     * @author Michael Naatjes
     * @license MIT
     */
    /**
     * CONSTANTS
     */
    require_once('constants/case_caps.php');
    require_once('constants/ext_mime_types.php');
    require_once('constants/mime_types_depreciated.php');
    /**
     * INI
     */
    require_once('ini/ini_errors_enable.php');
    require_once('ini/ini_parse_byte_str.php');
    /**
     * LOGGING
     * TODO: Integrate into Errors Object
     * TODO: Rename Errors Object HTTPErrors
     */
    require_once('logging/log_dump.php');
    require_once('logging/log_errors.php');
    /**
     * STRINGS
     */
    if(!function_exists('str_starts_with')){
        require_once 'strings/str_starts_with.php';
    }
    if(!function_exists('str_ends_with()')){
        require_once 'strings/str_ends_with.php';
    }
    require_once('strings/str_has_delimiter.php');
    require_once('strings/str_camel_to_kebab.php');
    require_once('strings/str_camel_to_snake.php');
    require_once('strings/str_snake_to_kebab.php');
    require_once('strings/str_replace_substr.php');
    /**
     * FILES
     */
    require_once 'files/is_valid_file.php';
    require_once 'files/normalize_path.php';
    require_once 'files/get_file_extension.php';
    require_once 'files/get_file_mimetype.php';
    require_once 'files/is_file_csv.php';
    require_once 'files/require_once_dir.php';
    require_once 'files/scandir_recursive.php';
    require_once 'files/is_path_absolute.php';
    /**
     * PATH
     */
    require_once('paths/validate_resource_path.php');
    require_once('paths/normalize_resource_path.php');
    /**
     * HTTP
     */
    require_once('http/sanitize_server_data.php');
    require_once('http/parse_query_parameters.php');
?>