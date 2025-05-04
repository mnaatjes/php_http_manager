<?php
    /**
     * Utility Properties for all Controller Classes/Objects
     * @since 04/25/25
     */
    /*----------------------------------------------------------*/
     /**
     * Constant Variables
     */
    /*----------------------------------------------------------*/
    define('QUERY_DELIMITER', ":");
    define('QUERY_OPERATORS', [
        '_ne'       => '!=',
        '_lt'       => '<',
        '_lte'      => '<=',
        '_gt'       => '>',
        '_gte'      => '>=',
        '_like'     => 'LIKE',
        '_in'       => 'IN',
        '_as'       => 'AS',
        '_before'   => '',
        '_after'    => '',
    ]);
    /*----------------------------------------------------------*/
    /**
     * Parse Query Parameters
     * @param array $query_params from $_GET array
     * @return array{
     *      clauses: array,
     *      filters: array,
     *      query: array
     * } parsed clauses and conditions(filters) and remaining query array
     */
    /*----------------------------------------------------------*/
    function parse_query_parameters(array $query_params){
        /**
         * Extract Query Fields
         */
        $fields = parseQueryFields($query_params);
        /**
         * Extract Query Clauses:
         * - $query_params passed by reference!
         */
        $clauses = parseQueryClauses($query_params);
        /**
         * Extract Query Filters:
         * - Declare $filters array
         * - Check remaining Query Params are not empty
         * - Parse Filters
         */
        $filters = [];
        if(!empty($query_params)){
            $filters = parseQueryFilters($query_params);
        }
        /**
         * Return Array
         */
        return [
            'fields'    => $fields,
            'filters'   => $filters,
            'clauses'   => $clauses,
            'query'     => $query_params
        ];
    }
    /*----------------------------------------------------------*/
    /**
     * Parse Fields
     * @param array $query_params Passed by REFERENCE;
     * @return array Array of column names; Default === [*]
     */
    /*----------------------------------------------------------*/
    function parseQueryFields(array &$query_params){
        /**
         * Search Query parameters for "fields" key:
         * - Declare $results array
         * - Check for 'fields' $_GET parameter
         * - Return $results
         */
        $results = [];
        if(array_key_exists("fields", $query_params)){
            // Grab values
            $values = $query_params['fields'];
            // Check for delimiter
            $pos = strpos($values, ",");
            if(is_int($pos)){
                // explode and assign
                foreach(explode(",", $values) as $field){
                    $results[] = trim($field);
                }
            } else {
                /**
                 * Assign $results
                 */
                $results[] = $values;
            }
            /**
             * Unset "Fields" from Query Parameters
             */
            unset($query_params['fields']);
        } else {
            /**
             * Assign Default Default
             */
            $results[] = '*';
        }
        /**
         * Return results
         */
        return $results;
    }
    /*----------------------------------------------------------*/
    /**
     * Parse Query Clauses 
     * - Finds LIMIT, HAVING, ORDER BY, etc... 
     * - Does NOT parse WHERE! WHERE evaluated in parseQueryFilters()
     * @param array $query_params from $_GET; Passed BY REFERENCE!
     * @return array Clauses from query parameter keys
     */
    /*----------------------------------------------------------*/
    function parseQueryClauses(array &$query_params){
        /**
         * Extract Clauses from Query Parameter Keys
         * - Split off keys: "sort" and "orderby"
         * - TODO: Manage LIMIT, HAVING, OFFSET
         * - Declare container $clauses
         * - Unset keys from reference arrays
         */
        $clauses = [];
        /**
         * Sort
         */
        $clause = "sort";
        if(array_key_exists($clause, $query_params)){
            $clauses[$clause][] = $query_params[$clause];
            unset($query_params[$clause]);
        }
        /**
         * ORDER BY
         */
        $clause = "orderBy";
        if(array_key_exists($clause, $query_params)){
            $clauses[$clause][] = $query_params[$clause];
            unset($query_params[$clause]);
        }
        /**
         * LIMIT
         */
        $clause = "limit";
        if(array_key_exists($clause, $query_params)){
            $clauses[$clause][] = $query_params[$clause];
            unset($query_params[$clause]);
        }
        /**
         * HAVING
         */
        $clause = "having";
        if(array_key_exists($clause, $query_params)){
            $clauses[$clause][] = $query_params[$clause];
            unset($query_params[$clause]);
        }
        /**
         * OFFSET
         */
        $clause = "offset";
        if(array_key_exists($clause, $query_params)){
            $clauses[$clause][] = $query_params[$clause];
            unset($query_params[$clause]);
        }
        /**
         * Merge "SORT" keys with "ORDER BY" keys
         * - Merge
         * - Unset "SORT"
         */
        if(array_key_exists('sort', $clauses)){
            /**
             * Check for orderBy to determine merge
             */
            if(array_key_exists('orderBy', $clauses)){
                $clauses['orderBy'] = array_merge($clauses['orderBy'], $clauses['sort']);
            } else {
                $clauses['orderBy'] = $clauses['sort'];
            }
            // Unset Sort from clauses array
            unset($clauses['sort']);
        }
        /**
         * Search for SQL Clause indicators in Query Parameter Values
         * - Copy $query_params for loop
         * - Find string position of delimiter
         * - Validate sorting direction
         * - Append to Clauses array
         * - Replace Query Param item if found
         */
        $query_arr = $query_params;
        foreach($query_arr as $param => $values){
            /**
             * Evaluate Param (key)
             * - Find position
             * - Split into key (prefix) and modifier (suffix)
             * - Validate modifier
             * - Unset $query_params key
             * - Replace with stripped $prefix and original values
             */
            $pos_key = strpos($param, QUERY_DELIMITER);
            if(is_int($pos_key)){
                // Split param
                $prefix = substr($param, 0, $pos_key);
                $suffix = strtoupper(substr($param, $pos_key + 1));
                // Validate modifier
                if($suffix === "ASC" || $suffix === "DESC"){
                    // Append to ORDER BY
                    $clauses['orderBy'][] = $param;
                    // Replace $query_param item
                    unset($query_params[$param]);
                    $query_params[$prefix] = $values;
                }
            }
            /**
             * Evaluate Values
             * - Find position of delimiter
             * - Split into value : modifier
             * - Validate modifier
             * - Parse param for operators (e.g. _gte, _lt...)
             * - Append to Clauses
             * - Replace value in $query_params
             */
            $pos_val = strpos($values, QUERY_DELIMITER);
            if(is_int($pos_val)){
                // Split values into val and modifier
                $prefix = substr($values, 0, $pos_val);
                $suffix = strtoupper(substr($values, $pos_val + 1));
                // Validate modifier
                if($suffix === "ASC" || $suffix === "DESC"){
                    /**
                     * Evaluate $param for operators (_gte, _lt,...)
                     * - Declare column from $param
                     * - Find last underscore position
                     * - Find match in OPERATORS
                     * - Strip and append to $clauses
                     * - Replace $query_params value with original $param and stripped value ($prefix)
                     */
                    $column = $param;
                    $op_pos = strrpos($param, '_');
                    if(is_int($op_pos)){
                        // Grab last underscored pos
                        $operator = substr($param, $op_pos);
                        // Validate is operator
                        if(in_array($operator, array_keys(QUERY_OPERATORS))){
                            // Rename $column
                            $column = substr($param, 0, $op_pos);
                        } else {
                            // $operator portion is part of the parameter
                            $column = $param;
                        }
                    }
                    // Append to ORDER BY
                    $clauses['orderBy'][] = "{$column}:{$suffix}";
                    // Replace $query_param item
                    $query_params[$param] = $prefix;
                }
            }
        }
        /**
         * Perform Filtering on Clauses
         * - Declare results array
         * - Search for missing sort direction
         * - Parse lists
         * - Arrange $clauses array in key => value order
         */
        $results = [];
        foreach($clauses as $clause => $values){
            /**
             * Evaluate based on Clause sub-array
             */
            switch($clause){
                /**
                 * ORDER BY
                 */
                case 'orderBy':
                    // Declare container
                    $results['orderBy'] = [];
                    // Loop values
                    foreach($values as $value){
                        /**
                         * Check value is a comma separated list
                         */
                        $pos_comma = strpos($value, ',');
                        if(is_int($pos_comma)){
                            /**
                             * Value is a list:
                             * - Explode
                             * - Loop
                             * - Validate
                             */
                            $list = explode(',', $value);
                            foreach($list as $val){
                                // Search for delimiter
                                $pos = strpos($val, QUERY_DELIMITER);
                                if(is_int($pos)){
                                    $param  = trim(substr($val, 0, $pos));
                                    $dir    = trim(strtoupper(substr($val, $pos + 1)));
                                    // Validate direction
                                    if($dir === "ASC" || $dir === "DESC"){
                                        $results['orderBy'][$param] = $dir;
                                    }
                                } else {
                                    // Missing default value
                                    $results['orderBy'][$val] = "ASC";
                                }
                            }
                        } else {
                            /**
                             * Value NOT a list:
                             * - Validate: check for delimiter
                             * - Assign defaults
                             */
                            $pos = strpos($value, QUERY_DELIMITER);
                            if(is_int($pos)){
                                // Split
                                $param   = substr($value, 0, $pos);
                                $dir     = strtoupper(substr($value, $pos + 1));
                                // Validate direction
                                if($dir === "ASC" || $dir === "DESC"){
                                    $results['orderBy'][$param] = $dir;
                                }
                            } else {
                                // Missing default value
                                $results['orderBy'][$value] = "ASC";
                            }
                        }
                    }
                    break;
                /**
                 * Default
                 */
                default: 
                    $results[$clause] = $values;
                    break;
            }
        }
        /**
         * Return clauses array and modified query array
         */
        return $results;
    }
    /*----------------------------------------------------------*/
    /**
     * Parse Query Filters
     * @param array $query_params from $_GET; Passed BY REFERENCE!
     * @return array{
     *      param: string,
     *      operator: string,
     *      values: array<string>
     * } Filters from $_GET
     */
    /*----------------------------------------------------------*/
    function parseQueryFilters(array &$query_params){
        /**
         * Perform Filtering on Query Parameters:
         * - Copy query params for loop
         * - Declare results array
         * - Parse Lists
         * - Parse Operators
         * - Unset $query_params
         */
        $query_arr  = $query_params;
        $results    = [];
        foreach($query_arr as $key => $value){
            /**
             * Search params for operators:
             * - Declare default operator
             * - Find position last underscore
             * - Validate last_underscore substring
             * - Define $parameter
             * - Define $operator
             * - Define values
             */
            $operator   = '=';
            $op_pos     = strrpos($key, '_');
            if(is_int($op_pos)){
                // Split
                $parameter       = trim(substr($key, 0, $op_pos));
                $last_underscore = trim(substr($key, $op_pos));
                // Validate operator
                if(in_array($last_underscore, array_keys(QUERY_OPERATORS))){
                    $operator = QUERY_OPERATORS[$last_underscore];
                } else {
                    // last underscore is part of the parameter
                    $parameter = $key;
                }
            } else {
                // Define parameter
                $parameter = $key;
            }
            // Declare parameter array
            $results[$parameter] = [
                'operator'  => $operator,
                'values'    => []
            ];
            /**
             * Check for $value delimiter
             */
            $pos_comma = strpos($value, ",");
            if(is_int($pos_comma)){
                /**
                 * Multiple values:
                 * - Explode and loop
                 * - Append to parameter array
                 */
                foreach(explode(',', $value) as $val){
                    $results[$parameter]['values'][] = $val;
                }
            } else {
                /**
                 * Singular value:
                 * - Append to parameter array
                 */
                $results[$parameter]['values'] = $value;
            }
            /**
             * Unset from $query_params array
             */
            unset($query_params[$key]);
        }
        /**
         * Return $results array
         */
        return $results;
    }
?>