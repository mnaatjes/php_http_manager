<?php
    /*----------------------------------------------------------*/
    /**
     * This is the default ViewEngine handler
     * 
     * @since 2.0   Initial:
     * - Created
     * - Assigns default "views" directory
     */
    /*----------------------------------------------------------*/
    class ViewEngine {
        /**
         * @var string $dir Views directory filepath reference
         */
        protected ?string $dir;
        /*----------------------------------------------------------*/
        /**
         * Constructor
         * 
         * @param null|string $directory Location of views directory; Default "/views" of main application level
         * @param string $permissions Default 0777
         */
        /*----------------------------------------------------------*/
        public function __construct(null|string $directory, string $permissions="0777"){
            /**
             * Set views directory
             */
            $this->dir = $this->setDirectory($directory, $permissions);
        }
        /*----------------------------------------------------------*/
        /**
         * Set views directory
         * 
         * @param null|string $dir Directory of views
         * @param int $perms Permissions int for unix systems | ignored for WIN systems
         * 
         * @return string|false Filepath to directory or false on failure
         */
        /*----------------------------------------------------------*/
        private function setDirectory(?string $dir, string $perms): string|false{
            /**
             * Declare default directory
             */
            $default_dir = './views';
            /**
             * Validate null
             */
            if(is_null($dir)){
                $dir = $default_dir;
            }
            /**
             * Validate dir:
             * - Check if exists
             * - Try mkdir if does not exist
             */
            if(!is_dir($dir) && function_exists('mkdir')){
                // Attempt mkdir for directory
                try {
                    // Declare result and mkdir
                    $result = mkdir($dir, $perms, true);
                    // validate created
                    if($result === false){
                        // Trigger error
                        $error = error_get_last();
                        trigger_error($error['message']);
                        // return false
                        return false;
                    }
                    // return filepath of directory on success
                    return $dir;
                } catch (Exception $err){
                    trigger_error($err->getMessage());
                    return false;
                }
            }
            /**
             * Return filepath
             */
            return $dir;
        }
        /*----------------------------------------------------------*/
        /**
         * Validate view filepath
         * 
         * @param string $viewname checks if the view 
         * 
         * @return bool true on found and valid
         */
        /*----------------------------------------------------------*/
        public function viewExists(string $view_name): bool{
            /**
             * Check for view properties
             */
            $props = $this->getView($view_name);
            if(is_null($props)){
                /**
                 * View file not found
                 */
                return false;
            } else if(is_array($props) && isset($props['path'])){
                /**
                 * Check for path 
                 * Validate path
                 */
                if(is_file($props['path'])){
                    /**
                     * File found and valid
                     */
                    return true;
                } else {
                    /**
                     * File path could not be resolved
                     */
                    return false;
                }
            } else {
                /**
                 * Return default
                 */
                return false;
            }
        }
        /*----------------------------------------------------------*/
        /**
         * Gets data about the view_name and returns the data:
         * - Will return first instance: e.g. if: filename.php and filename.html exist, filename.html will be returned
         * - Cannot have multiple files of different types with the same name!
         * 
         * @param string $view_name Name of file within "views" directory
         * 
         * @return null|array
         */
        /*----------------------------------------------------------*/
        public function getView(string $view_name): null|array{
            /**
             * Declare results container
             */
            $results = null;
            /**
             * Collect view properties
             * - View name from user
             * - Normalized view name with 
             */
            $normal = $this->normalize($view_name);
            /**
             * Find file and extension:
             * - Scan dir
             * - Check for contents
             * - Filter out "." and ".."
             * - Find file
             */
            $contents   = scandir($this->dir);
            $file       = $contents === false ? null : array_reduce($contents, function($acc, $curr) use($normal){
                if($curr !== '.' && $curr !== '..'){
                    $pos = strpos($curr, $normal);
                    if(is_int($pos) && pathinfo($curr, PATHINFO_FILENAME) === $normal){
                        $acc = $curr;
                    }
                }
                return $acc;
            }, null);
            /**
             * Validate file found
             */
            if(!is_null($file)){
                /**
                 * Validate file and get information:
                 * - Validate not null
                 * - Get ext
                 * - Get mimetype
                 * - Return true if present
                 */
                $path   = $this->dir . DIRECTORY_SEPARATOR . $file;
                $ext    = pathinfo($path, PATHINFO_EXTENSION);
                $mime   = isset(EXT_MIME_TYPES[$ext]) ? EXT_MIME_TYPES[$ext] : null;
                /**
                 * Assign view property data to results
                 */
                $results = [
                    'name'  => $view_name,
                    'path'  => $path,
                    'ext'   => $ext,
                    'mime'  => $mime
                ];
            }
            /**
             * Return default
             */
            return $results;
        }
        /*----------------------------------------------------------*/
        /**
         * Normalize view filepath:
         * - Strip leading or trailing 
         * 
         * @param null|string $view_name
         * 
         * @return null|string Null on failure
         */
        /*----------------------------------------------------------*/
        protected function normalize(string $view_name): null|string{
            /**
             * Check if string
             */
            $result = null;
            if(is_string($view_name)){
                /**
                 * Set to lowercase
                 */
                $view_name = strtolower($view_name);
                /**
                 * Strip leading and trailing slashes
                 * Remove extension if present
                 */
                $result  = trim($view_name, '/');
                $pos_ext = strrpos($view_name, '.');
                /**
                 * Check for leading or ending directory separators
                 * Check for extension
                 */
                if(is_int($pos_ext)){
                    $result = substr($view_name, 0, $pos_ext);
                }
            }
            /**
             * Return default
             */
            return $result;
        }
        /*----------------------------------------------------------*/
        /**
         * Render view:
         * - Normalizes $view_name
         * - Validates and checks if it exists
         * - Grabs view properties
         * - Renders view
         * - TODO: Create const in Class for error codes
         * 
         * @param string $view_name
         * @param null|array $data Data to be applied to template or file; Default NULL
         * @param int $data_flag Sets flag for extract(); Default === EXTR_OVERWRITE
         * 
         * @return array Body of data from file (with data properties assigned) | Errors on failure
         */
        /*----------------------------------------------------------*/
        public function render(string $view_name, null|array $data=null, int $data_flag=EXTR_OVERWRITE): array{
            /**
             * Declare properties:
             * - Content container
             * - Errors container
             */
            $contents   = null;
            $errors     = null;
            /**
             * Validate $view name and get properties
             */
            if($this->viewExists($view_name)){
                /**
                 * File exists:
                 * - Grab properties
                 * - Attach data (if present)
                 * - Render
                 */
                $props = $this->getView($view_name);
                if(isset($props['path'])){
                    /**
                     * Validate data and extract if present:
                     * - Check for null | array
                     * - Validate is assoc array
                     * - Destructure key, values into their own variables
                     * - Overwrites variable is same name exists
                     */
                    if(is_array($data) && !empty($data)){
                        /**
                         * Check if data array is assoc with string keys
                         */
                        $is_assoc = array_every(array_keys($data), function($_, $key){
                            return is_string($key);
                        });
                        /**
                         * Destructure Data
                         */
                        if($is_assoc !== false){
                            /**
                             * Extract data from $data container
                             */
                            extract($data, $data_flag);
                        } else {
                            /**
                             * Failed to extract data:
                             * - Set Error
                             */
                            $errors['message'] = 'Could not parse $data array supplied to '.__FUNCTION__;
                        }
                    }
                    /**
                     * Render content:
                     * - Start output buffer
                     * - Include filepath
                     * - End output buffer and capture stream
                     */
                    ob_start();
                    include($props['path']);
                    $contents = ob_get_clean();
                    /**
                     * Validate Contents
                     */
                    if(!is_string($contents)){
                        /**
                         * Failure to capture stream:
                         * - Set error message
                         * - Set content to NULL
                         */
                        $errors['message'] = sprintf('Failed to extract contents from View: %s in %s', $view_name, __FUNCTION__);
                        $contents = null;
                    }
                } else {
                    /**
                     * Could not resolve path
                     * - Set errors
                     */
                    $errors['message'] = sprintf('Unable to resolve path to View: %s in %s', $view_name, __FUNCTION__);
                }
            } else {
                /**
                 * View does not exist:
                 * - Set error message
                 */
                $errors['message'] = sprintf('View: %s could not be resolved from %s directory in !', $view_name, $this->dir, __FUNCTION__);
            }
            /**
             * Return $results container
             */
            return [
                'content'   => $contents,
                'errors'    => $errors,
                'mime'      => 'text/html'
            ];
        }
    }
?>