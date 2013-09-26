<?php

class C_Fs extends C_Component
{
	static	$_instances = array();
	var		$_document_root;

	/**
	 * Gets an instance of the FS utility
	 * @param mixed $context
	 * @return C_Fs
	 */
	static function &get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}

	/**
	 * Defines the instance of the FS utility
	 * @param mixed $context	the context in this case is the product
	 */
	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Fs_Instance_Methods');
		$this->implement('I_Fs');
	}

	function initialize()
	{
		parent::initialize();
		$this->_document_root = $this->set_document_root($_SERVER['DOCUMENT_ROOT']);
	}
}

class Mixin_Fs_Instance_Methods extends Mixin
{
    
        function add_trailing_slash($path)
        {
            if (substr($path, -1) != '/') $path .= '/';
            return $path;
        }
    
    
        /**
         * Returns a calculated path to a file
         * @param string $path
         * @param string $module
         * @param boolean $relpath
         * @returns string
         */
        function get_abspath($path, $module=FALSE, $relpath=FALSE)
        {
            // Wel'l assume that we're to calculate the path relative to
            // the site document root
            $retval = $path;
            if (strpos($path, $this->get_document_root()) === FALSE) {
                $retval = $this->join_paths(
                    $this->get_document_root(),
                    $path
                );
            }
            
            // If a module is provided, then we should calculate the path
            // relative to the module directory
            if ($module) {
                if (($module_dir = $this->get_registry()->get_module_dir($module))) {
                    $retval = $this->join_paths($module_dir, $path);
                }
                else {
                    $retval = $this->join_path(
                        $this->get_document_root(), $module, $path
                    );
                }
            }
            
            // Return the calculated path relative to the document root
            if ($relpath) $retval = $this->object->remove_path_segment(
                $retval, $this->get_document_root()
            );
            
            return $retval;
        }
        
        
        /**
         * Returns a calculated relpath to a particular file
         * @param string $path
         * @param string $module
         * @return string
         */
        function get_relpath($path, $module=FALSE)
        {
            return $this->object->get_abspath($path, $module, TRUE);
        }
        
        /**
         * Removes a path segment from a url or filesystem path
         * @param string $path
         * @param string $segment
         * @return string
         */
        function remove_path_segment($path, $segment)
        {
            if (substr($segment, -1) == '/') $segment = substr($segment, 0, -1);
            $parts = explode($segment, $path);
            return $this->join_paths($parts);
        }
    
    
	/**
	 * Gets the absolute path to a file/directory for a specific Pope product
     *
     * If the path doesn't exist, then NULL is returned
	 * @param string $path
	 * @param string $module
     * @returns string|NULL
	 */
	function find_abspath($path, $module=FALSE, $relpath=FALSE, $search_paths=array())
	{
		$retval = NULL;

        // Ensure that we weren't passed a module id in the path
        if (!$module)
            list($path, $module) = $this->object->parse_formatted_path($path);

		if (@file_exists($path))
        {
            $retval = $path;
        }
		else {

			// Ensure that we know where to search for the file
			if (!$search_paths)
                $search_paths = $this->object->get_search_paths($path, $module);

            // See if the file is located under one of the search paths directly
            foreach ($search_paths as $dir) {
                if (@file_exists($this->join_paths($dir, $path))) {
                    $retval = $this->join_paths($dir, $path);
                    break;
                }
            }

            // Use rglob to find the file
            if (!$retval) foreach ($search_paths as $dir) {
                if (($retval = $this->object->_rglob($dir, $path))) {
                    break;
                }
            }

            // Return the relative path if we're to do so
            if ($relpath) {
                $retval = $this->object->remove_path_segment($retval, $this->get_document_root());
            }
        }

        return $retval;
    }

	/**
	 * Returns a list of directories to search for a particular filename
	 * @param string $path
	 * @param string $module
	 * @return array
	 */
	function get_search_paths($path, $module=FALSE)
	{
		$append_module = FALSE;

		// Ensure that we weren't passed a module id in the path
		if (!$module) list($path, $module) = $this->object->parse_formatted_path($path);

		// Directories to search
		$directories = array();

		// If a name of a module has been provided, then we need to search within
		// that directory first
		if ($module) {

			// Were we given a module id?
			if (($module_dir = $this->get_registry()->get_module_dir($module))) {
				$directories[] = $module_dir;
			}
			else {
				$append_module = TRUE;
			}
		}

		// Add product's module directories
		foreach ($this->get_registry()->get_product_list() as $product_id) {
			$product_dir = $this->get_registry()->get_product_module_path($product_id);
			if ($append_module) $directories[] = $this->join_paths(
				$product_dir, $module
			);
			$directories[] = $product_dir;
		}

		// If all else fails, we search from the document root
		$directories[] = $this->get_document_root();

		return $directories;
	}

	/**
	 * Searches for a file recursively
     *
	 * @param string $base_path
	 * @param string $file
	 * @return string
	 */
	function _rglob($base_path, $file)
	{
		$retval = NULL;

		$results = @file_exists($this->join_paths($base_path, $file));

		// Must be located in a sub-directory
		if (!$results)
        {
            // the modules cache a list of all their files when they are initialized. Ask POPE for our current
            // modules and inspect their file listing to determine which module provides what we need
            $modules = $this->object->get_registry()->get_module_list();
            foreach ($modules as $module) {
                $module_file_list = array_values($this->object->get_registry()->get_module($module)->get_type_list());
                $module_dir = $this->object->get_registry()->get_module_dir($module);

                $variations = array(
                    $file,
                    ltrim($file, DIRECTORY_SEPARATOR)
                );

                foreach ($variations as $variant) {
                    if (in_array($variant, $module_file_list))
                    {
                        $retval = $this->join_paths($module_dir, $variant);
                        break 2;
                    }
                }
            }
		}
		else {
            $retval = $this->join_paths($base_path, $file);
        }

		return $retval;
	}

	/**
	 * Gets the relative path to a file/directory for a specific Pope product.
         * If the path doesn't exist, then NULL is returned
	 * @param type $path
	 * @param type $module
         * @returns string|NULL
	 */
	function find_relpath($path, $module=FALSE)
	{
		return $this->object->find_abspath($path, $module, TRUE);
	}


	/**
	 * Joins multiple path segments together
	 * @return string
	 */
	function join_paths()
	{
		$segments = array();
		$retval = array();
        $protocol = NULL;
		$params = func_get_args();
		$this->_flatten_array($params, $segments);

        // if a protocol exists strip it from the string and store it for later
        $pattern = "#^[a-zA-Z].+://#i";
        preg_match($pattern, $segments[0], $matches);
        if (!empty($matches)) {
            $protocol = reset($matches);
            $segments[0] = preg_replace($pattern, '', $segments[0], 1);
        }

		foreach ($segments as $segment) {
            $segment = trim($segment, '/\\');
            $pieces = array_values(preg_split('/[\/\\\\]/', $segment));

            // determine if each piece should be appended to $retval
            foreach ($pieces as $ndx => $val) {
                $one = array_search($val, $retval);
                $two = array_search($val, $pieces);
                $one = (FALSE === $one ? 0 : count($one) + 1);
                $two = (FALSE === $two ? 0 : count($two) + 1);
                if (!empty($protocol)) {
					$existing_val = isset($retval[$ndx]) ? $retval[$ndx] : NULL;
                    if ($existing_val !== $val || $two >= $one)
                        $retval[] = $val;
                }
                else {
					$existing_val = isset($retval[$ndx]) ? $retval[$ndx] : NULL;
                    if ($existing_val !== $val && $two >= $one)
                        $retval[] = $val;
                }
            }

		}

		$retval = $protocol . implode('/', $retval);

        if ((empty($protocol) && 'WINNT' !== PHP_OS)
            && strpos($retval, '/') !== 0
            && is_null($protocol)
            && !@file_exists($retval))
        {
            $retval = '/' . $retval;
        }

		return $retval;
	}

	function _flatten_array($obj, &$arr)
	{
		if (is_array($obj)) {
			foreach ($obj as $inner_obj) $this->_flatten_array($inner_obj, $arr);
		}
		elseif ($obj) $arr[] = $obj;
	}

	/**
	 * Parses the path for a module and filename
	 * @param string $str
	 */
	function parse_formatted_path($str)
	{
		$module = FALSE;
		$path	= $str;
		$parts	= explode('#', $path);
		if (count($parts) > 1) {
			$module = array_shift($parts);
			$path   = array_shift($parts);
		}
		return array($path, $module);
	}

	/**
	 * Gets the document root for this application
	 * @return string
	 */
	function get_document_root()
	{
		return $this->_document_root;
	}

	/**
	 * Sets the document root for this application
	 * @param type $value
	 * @return type
	 */
	function set_document_root($value)
	{
        // Even for windows hosts we force '/' as the path separator
		return $this->_document_root = untrailingslashit(str_replace('\\', '/', $value));
	}
}
