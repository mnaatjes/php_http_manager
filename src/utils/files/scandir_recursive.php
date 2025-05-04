<?php
    /**
     * Recursively runs scandir() to find all nested files:
     * - Case Sensitive!
     * - Validates path string
     * - Normalizes using normalize_path()
     * - Resolves path with realpath()
     * - Checks if resolved path exists
     * - Checks for empty directories
     * - Implements scandir() recursively
     * @param string|null $path Directory path to scan; Default null
     * @param array{
     * - sorting_order: int Value from constant variables: SCANDIR_SORT_ASCENDING (Default), SCANDIR_SORT_DESC, SCANDIR_SORT_NONE
     * - context: array Associative Array to define properties of the scandir() return array. Uses stream_context_create(); Default null
     * - ext: array|null Extensions to search for within each directory; Default = null
     * - with_paths: bool Determines if the returned array includes the filepaths to nested elements; Default = true
     * } [$options] scandir() parameters and additional parameters specific to scandir_recursive()
     * 
     * @return array|null Array of path contents | Null on failure or empty directory
     */
    function scandir_recursive(?string $path=null, array $options=[]): ?array{
        /**
         * Validate path parameter
         */
        if(is_null($path) || empty($path)){
            return null;
        }
        /**
         * Resolve to realpath:
         * - Check if successful
         */
        $realpath = realpath($path);
        if(!$realpath){
            // Could not resolve path
            trigger_error(sprintf('Could not resolve path: %s with realpath()', $path));
            return null;
        }
        /**
         * Check if path exists as directory
         */
        if(!is_dir($realpath)){
            trigger_error(sprintf('Resolved path %s is not a directory!', $realpath));
            return null;
        }
        /**
         * Set default values for $options.
         * Check for empty options parameter.
         * Evaluate $options:
         * - Sorting Order
         * - Context
         * - Extensions
         * - Return with paths
         */
        // Default Values
        $sort_order = SCANDIR_SORT_ASCENDING;
        $context    = null;
        $extensions = null;
        $with_paths = true;
        // Check if options is NOT empty and apply optional parameters:
        if(!empty($options)){
            // Sorting Order
            if(isset($options['sorting_order']) && is_int($options['sorting_order'])){
                $sort_order = $options['sorting_order'];
            }
            // Context
            if(isset($options['context']) && is_array($options['context'])){
                $options['context'] = $options['context'];
                $context = stream_context_create($options['context']);
            }
            // Extensions
            if(isset($options['ext']) && is_array($options['ext'])){
                $extensions = $options['ext'];
            }        
            // Return with paths
            if(isset($options['with_paths']) && is_string($options['with_paths'])){
                $with_paths = $options['with_paths'];
            }
        }
        /**
         * Normalize resolved path
         * - Check for ending Directory Separator
         * - Append Separator if missing from directory
         */
        $normal_path = normalize_path($realpath);
        if(!str_ends_with($normal_path, DIRECTORY_SEPARATOR)){
            $normal_path = $normal_path . DIRECTORY_SEPARATOR;
        }
        /**
         * Use Scandir and evaluate contents
         * - Check if $context resource set
         */
        $contents = is_resource($context) ? scandir($path, $sort_order, $context) : scandir($path, $sort_order);
        $results  = [];
        if(empty($contents)){
            // Empty Directory
            return null;
        }
        /**
         * Loop contents array
         */
        foreach($contents as $item){
            // Skip over empty elements
            if($item === '' || $item === '.' || $item === '..'){
                continue;
            } else {
                $item_path = $normal_path . $item;
                /**
                 * Check for directory or file
                 */
                if(is_file($item_path)){
                    /**
                     * Item is a file:
                     * - Check extensions array
                     * - Check for extensions to include
                     * - Check if include_path is set
                     * - Otherwise, grab all contents
                     */
                    if(is_null($extensions) || empty($extensions)){
                        // Grab all contents
                        $results[] = $with_paths ? $item_path : $item;
                    } else {
                        /**
                         * Loop extensions array for matching elements:
                         * - Append periods if missing
                         */
                        foreach($extensions as $ext){
                            // Normalize file extension
                            $pos = strpos($ext, '.');
                            $ext = !is_int($pos) ? '.' . $ext : $ext;
                            if(str_ends_with($item_path, $ext)){
                                $results[] = $with_paths ? $item_path : $item;
                            }
                        }
                    }
                } else {
                    /**
                     * Item likely a directory:
                     * - Check if directory exists
                     * - Recursively append data
                     */
                    if(is_dir($item_path)){
                        $scan_dir = scandir_recursive($item_path, $options);
                        // Validate result
                        if(!is_null($scan_dir) && !empty($scan_dir)){
                            /**
                             * Check if $with_paths is set:
                             * - Unset: return nested arrays
                             * - Set: merged arrays
                             */
                            if(!$with_paths){
                                // Nested Arrays
                                $results[] = $scan_dir;
                            } else {
                                // Merge elements
                                $results = array_merge($results, $scan_dir);
                            }
                        }
                    }
                }
            }
        }
        /**
         * Check for empty results
         */
        if(empty($results)){
            return [];
        } else {
            // Return results
            return $results;
        }
        /**
         * Return default
         */
        return null;
    }
?>