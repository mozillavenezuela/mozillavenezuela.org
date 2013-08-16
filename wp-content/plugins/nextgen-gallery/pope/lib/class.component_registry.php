<?php

/**
 *  A registry of registered products, modules, adapters, and utilities.
 */
class C_Component_Registry
{
    static  $_instance = NULL;
    var     $_meta_info = array(); /* Contains a cached mapping of module_id -> module_info (including the path the module was installed to) */
    var     $_default_path = NULL;
    var     $_modules = array();
    var     $_products = array();
    var     $_adapters = array();
    var     $_utilities = array();
    var     $_module_type_cache = array();
    var     $_module_type_cache_count = 0;


    /**
     * This is a singleton object
     */
    private function __construct()
    {
			// Create an autoloader
			spl_autoload_register(array($this, '_module_autoload'), TRUE);
    }


    /**
     * Returns a singleton
     * @return C_Component_Registry()
     */
    static function &get_instance()
    {
        if (is_null(self::$_instance)) {
            $klass = get_class();
            self::$_instance = new $klass();
        }
        return self::$_instance;
    }

    /**
     * Saves the registry to disk
     * @param string $config_file
     */
    function save($config_file)
    {
        $fp = FALSE;
        $retval = TRUE;

        try {
            $fp = fopen($config_file, 'w');
            fwrite($fp, json_encode(array(
                'modules' => $this->_modules,
                'products' => $this->_products,
                'adapters' => $this->_adapters,
                'utilities'=> $this->_utilities
            )));
        }
        catch (Exception $e) {
            if ($fp) fclose($fp);
            $retval = FALSE;
        }
        if ($fp) fclose($fp);

        return $retval;
    }


    function load($config_file)
    {
        $fp = FALSE;
        $retval = TRUE;

        try {
            $fp = fopen($config_file);
            $json = json_decode(fread($fp), TRUE);
            $this->_modules = array_merge($this->_modules, $json['modules']);
            $this->_products = array_merge($this->_products, $json['products']);
            $this->_adapters = array_merge($this->_adapters, $json['adapters']);
            $this->_utilities = array_merge($this->_utilities, $json['utilities']);
        }
        catch (Exception $e) {
            if ($fp) fclose($fp);
            $retval = FALSE;
        }
        if ($fp) fclose($fp);

        return $retval;
    }


    /**
     * Adds a path in the search paths for loading modules
     * @param string $path
     * @param bool $recurse - note, it will only recurse 1 level in the hierarchy
     * @param bool $load_all - loads all modules found in the path
     */
    function add_module_path($path, $recurse = false, $load_all = false)
    {
    	if ($this->get_default_module_path() == null)
    	{
    		$this->set_default_module_path($path);
    	}

    	$scan = $this->_scan_module_path($path, $recurse);

    	if ($scan != null)
    	{
    		$this->_meta_info = array_merge($this->_meta_info, $scan);

    		if ($load_all)
    		{
    			$module_list = array_keys($scan);
    			$load_list = array();
    			$count = count($module_list);
    			$ret = true;

    			for ($i = 0; $i < $count; $i++)
    			{
    				$module_id = $module_list[$i];
    				$info = isset($scan[$module_id]) ? $scan[$module_id] : null;
  					$before_index = null;

    				if (isset($info['before-list']))
    				{
    					$before_list = $info['before-list'];

    					foreach ($before_list as $before_module)
    					{
    						$load_index = array_search($before_module, $load_list);

    						if ($load_index !== false)
    						{
    							if ($before_index === null || $load_index < $before_index)
    							{
    								$before_index = $load_index;
    							}
    						}
    					}
    				}

  					if ($before_index !== null)
  					{
  						array_splice($load_list, $before_index, 0, array($module_id));
  					}
  					else
  					{
  						$load_list[] = $module_id;
  					}
    			}

    			foreach ($load_list as $module_id)
    			{
    			  $loaded = $this->load_module($module_id);
    				$ret = $ret && $loaded;
    			}

    			return $ret;
    		}

    		return true;
    	}

    	return false;
    }


    /**
     * Retrieves the default module path (Note: this is just the generic root container path for modules)
     * @return string
     */
    function get_default_module_path()
    {
    	return $this->_default_path;
    }


    /**
     * Sets the default module path (Note: this is just the generic root container path for modules)
     * @param string $path
     */
    function set_default_module_path($path)
    {
    	$this->_default_path = $path;
    }


    /**
     * Retrieves the module path
     * @param string $module_id
     * @return string
     */
    function get_module_path($module_id)
    {
    	if (isset($this->_meta_info[$module_id])) {
    		$info = $this->_meta_info[$module_id];

    		if (isset($info['path'])) {
    			return $info['path'];
    		}
    	}

    	return null;
    }


    /**
     * Retrieves the module installation directory
     * @param string $module_id
     * @return string
     */
    function get_module_dir($module_id)
    {
    	$path = $this->get_module_path($module_id);

    	if ($path != null) {
    		return dirname($path);
    	}

    	return null;
    }


    /**
     * Loads a module's code according to its dependency list
     * @param string $module_id
     */
    function load_module($module_id)
    {
    	return $this->_load_module_internal($module_id);
    }

    function load_all_modules($type = null)
    {
    	$modules = $this->get_known_module_list();
    	$ret = true;

    	foreach ($modules as $module_id)
    	{
    		if ($type == null || $this->get_module_meta($module_id, 'type') == $type) {
    			$ret = $this->load_module($module_id) && $ret;
    		}
    	}

    	return $ret;
    }


    /**
     * Initializes a previously loaded module
     * @param string $module_id
     */
    function initialize_module($module_id)
    {
		$retval = FALSE;
    	if (isset($this->_modules[$module_id])) {
    		$module = $this->_modules[$module_id];

    		if (!$module->initialized) {
				if ($module->has_method('initialize'))
					$module->initialize();

    			$module->initialized = true;
    		}
			$retval = TRUE;
    	}
		return $retval;
    }


	/**
	 * Initializes an already loaded product
	 * @param string $product_id
	 * @return bool
	 */
	function initialize_product($product_id)
	{
		return $this->initialize_module($product_id);
	}


    /**
     * Initializes all previously loaded modules
     */
    function initialize_all_modules()
    {
    	$module_list = $this->get_module_list();

    	foreach ($module_list as $module_id)
    	{
    		$this->initialize_module($module_id);
    	}
    }


    /**
     * Adds an already loaded module to the registry
     * @param string $module_id
     * @param C_Base_Module $module_object
     */
    function add_module($module_id, $module_object)
    {
    	if (!isset($this->_modules[$module_id])) {
    		$this->_modules[$module_id] = $module_object;
    	}
    }


    /**
     * Deletes an already loaded module from the registry
     * @param string $module_id
     */
    function del_module($module_id)
    {
    	if (isset($this->_modules[$module_id])) {
    		unset($this->_modules[$module_id]);
    	}
    }


    /**
     * Retrieves the instance of the registered module. Note: it's the instance of the module object, so the module needs to be loaded or this function won't return anything. For module info returned by scanning (with add_module_path), look at get_module_meta
     * @param string $module_id
     * @return C_Base_Module
     */
    function get_module($module_id)
    {
    	if (isset($this->_modules[$module_id])) {
    		return $this->_modules[$module_id];
    	}

    	return null;
    }

    function get_module_meta($module_id, $meta_name)
    {
    	$meta = $this->get_module_meta_list($module_id);

    	if (isset($meta[$meta_name])) {
    		return $meta[$meta_name];
    	}

    	return null;
    }

    function get_module_meta_list($module_id)
    {
    	if (isset($this->_meta_info[$module_id])) {
    		return $this->_meta_info[$module_id];
    	}

    	return null;
    }

    /**
     * Retrieves a list of instantiated module ids
     * @return array
     */
    function get_module_list()
    {
    	return array_keys($this->_modules);
    }

    /**
     * Retrieves a list of registered module ids, including those that aren't loaded (i.e. get_module() call with those unloaded ids will fail)
     * @return array
     */
    function get_known_module_list()
    {
    	return array_keys($this->_meta_info);
    }


    function load_product($product_id)
    {
    	return $this->load_module($product_id);
    }

    function load_all_products()
    {
    	return $this->load_all_modules('product');
    }

    /**
     * Adds an already loaded product in the registry
     * @param string $product_id
     * @param C_Base_Module $product_object
     */
    function add_product($product_id, $product_object)
    {
    	if (!isset($this->_products[$product_id])) {
    		$this->_products[$product_id] = $product_object;
    	}
    }


    /**
     * Deletes an already loaded product from the registry
     * @param string $product_id
     */
    function del_product($product_id)
    {
    	if (isset($this->_products[$product_id])) {
    		unset($this->_products[$product_id]);
    	}
    }


    /**
     * Retrieves the instance of the registered product
     * @param string $product_id
     * @return C_Base_Module
     */
    function get_product($product_id)
    {
    	if (isset($this->_products[$product_id])) {
    		return $this->_products[$product_id];
    	}

    	return null;
    }

    function get_product_meta($product_id, $meta_name)
    {
    	$meta = $this->get_product_meta_list($product_id);

    	if (isset($meta[$meta_name])) {
    		return $meta[$meta_name];
    	}

    	return null;
    }

    function get_product_meta_list($product_id)
    {
    	if (isset($this->_meta_info[$product_id]) && $this->_meta_info[$product_id]['type'] == 'product') {
    		return $this->_meta_info[$product_id];
    	}

    	return null;
    }


    /**
     * Retrieves the module installation path for a specific product (Note: this is just the generic root container path for modules of this product)
     * @param string $product_id
     * @return string
     */
    function get_product_module_path($product_id)
    {
    	if (isset($this->_meta_info[$product_id])) {
    		$info = $this->_meta_info[$product_id];

    		if (isset($info['product-module-path'])) {
    			return $info['product-module-path'];
    		}
    	}

    	return null;
    }


    /**
     * Sets the module installation path for a specific product (Note: this is just the generic root container path for modules of this product)
     * @param string $product_id
     * @param string $module_path
     */
    function set_product_module_path($product_id, $module_path)
    {
    	if (isset($this->_meta_info[$product_id])) {
    		$this->_meta_info[$product_id]['product-module-path'] = $module_path;
    	}
    }


    /**
     * Retrieves a list of instantiated product ids
     * @return array
     */
    function get_product_list()
    {
    	return array_keys($this->_products);
    }

    /**
     * Retrieves a list of registered product ids, including those that aren't loaded (i.e. get_product() call with those unloaded ids will fail)
     * @return array
     */
    function get_known_product_list()
    {
    	$list = array_keys($this->_meta_info);
    	$return = array();

    	foreach ($list as $module_id)
    	{
    		if ($this->get_product_meta_list($module_id) != null)
    		{
    			$return[] = $module_id;
    		}
    	}

    	return $return;
    }


    /**
     * Registers an adapter for an interface with specific contexts
     * @param string $interface
     * @param string $class
     * @param array $contexts
     */
    function add_adapter($interface, $class, $contexts=FALSE)
    {
        // If no specific contexts are given, then we assume
        // that the adapter is to be applied in ALL contexts
        if (!$contexts) $contexts = array('all');
        if (!is_array($contexts)) $contexts = array($contexts);

        if (!isset($this->_adapters[$interface])) {
            $this->_adapters[$interface] = array();
        }

        // Iterate through each specific context
        foreach ($contexts as $context) {
            if (!isset($this->_adapters[$interface][$context])) {
                $this->_adapters[$interface][$context] = array();
            }
            $this->_adapters[$interface][$context][] = $class;
        }
    }


    /**
     * Removes an adapter for an interface. May optionally specifify what
     * contexts to remove the adapter from, leaving the rest intact
     * @param string $interface
     * @param string $class
     * @param array $contexts
     */
    function del_adapter($interface, $class, $contexts=FALSE)
    {
        // Ensure that contexts is an array of contexts
        if (!$contexts) $contexts = array('all');
        if (!is_array($contexts)) $contexts = array($contexts);

        // Iterate through each context for an adapter
        foreach ($this->_adapters[$interface] as $context => $classes) {
            if (!$context OR in_array($context, $contexts)) {
                $index = array_search($class, $classes);
                unset($this->_adapters[$interface][$context][$index]);
            }
        }
    }


    /**
     * Apply adapters registered for the component
     * @param C_Component $component
     * @return C_Component
     */
    function &apply_adapters(C_Component &$component)
    {
        // Iterate through each adapted interface. If the component implements
        // the interface, then apply the adapters
        foreach ($this->_adapters as $interface => $contexts) {
            if ($component->implements_interface($interface)) {


                // Determine what context apply to the current component
                $applied_contexts = array('all');
                if ($component->context) {
					$applied_contexts[] = $component->context;
					$applied_contexts = $this->_flatten_array($applied_contexts);
                }

                // Iterate through each of the components contexts and apply the
                // registered adapters
                foreach ($applied_contexts as $context) {
                    if (isset($contexts[$context])) {
                        foreach ($contexts[$context] as $adapter) {
                            $component->add_mixin($adapter, TRUE);
                        }
                    }

                }
            }
        }

        return $component;
    }


    /**
     * Adds a utility for an interface, to be used in particular contexts
     * @param string $interface
     * @param string $class
     * @param array $contexts
     */
    function add_utility($interface, $class, $contexts=FALSE)
    {
        // If no specific contexts are given, then we assume
        // that the utility is for ALL contexts
        if (!$contexts) $contexts = array('all');
        if (!is_array($contexts)) $contexts = array($contexts);

        if (!isset($this->_utilities[$interface])) {
            $this->_utilities[$interface] = array();
        }

        // Add the utility for each appropriate context
        foreach ($contexts as $context) {
            $this->_utilities[$interface][$context] = $class;
        }
    }


    /**
     * Deletes a registered utility for a particular interface.
     * @param string $interface
     * @param array $contexts
     */
    function del_utility($interface, $contexts=FALSE)
    {
        if (!$contexts) $contexts = array('all');
        if (!is_array($contexts)) $contexts = array($contexts);

        // Iterate through each context for an interface
        foreach ($this->_utilities[$interface] as $context => $class) {
            if (!$context OR in_array($context, $contexts)) {
                unset($this->_utilities[$interface][$context]);
            }
        }
    }

	/**
	 * Gets the class name of the component providing a utility implementation
	 * @param string $interface
	 * @param string|array $context
	 * @return string
	 */
	function get_utility_class_name($interface, $context=FALSE)
	{
		return $this->_retrieve_utility_class($interface, $context);
	}


    /**
     * Retrieves an instantiates the registered utility for the provided instance.
     * The instance is a singleton and must provide the get_instance() method
     * @param string $interface
     * @param string $context
     * @return C_Component
     */
    function get_utility($interface, $context=FALSE)
    {
        if (!$context) $context='all';
        $class = $this->_retrieve_utility_class($interface, $context);
		return call_user_func("{$class}::get_instance", $context);
    }


	/**
	 * Flattens an array of arrays to a single array
	 * @param array $array
	 * @param array $parent (optional)
	 * @param bool $exclude_duplicates (optional - defaults to TRUE)
	 * @return array
	 */
	function _flatten_array($array, $parent=NULL, $exclude_duplicates=TRUE)
	{
		if (is_array($array)) {

			// We're to add each element to the parent array
			if ($parent) {
				foreach ($array as $index => $element) {
					foreach ($this->_flatten_array($array) as $sub_element) {
						if ($exclude_duplicates) {
							if (!in_array($sub_element, $parent)) {
								$parent[] = $sub_element;
							}
						}
						else $parent[] = $sub_element;
					}
				}
				$array = $parent;
			}

			// We're starting the process..
			else {
				$index = 0;
				while (isset($array[$index])) {
					$element = $array[$index];
					if (is_array($element)) {
						$array = $this->_flatten_array($element, $array);
						unset($array[$index]);
					}
					$index += 1;
				}
				$array = array_values($array);
			}
		}
		else {
			$array = array($array);
		}

		return $array;
	}


    /**
     * Returns a list of paths under a specific location, optionally by regex matching their names
     * @param string $path starting path
     * @param string $regex matched against file basename, not full path
     * @param int $recurse recurse level
     */
		function _get_file_list($path, $recurse = null, $regex = null)
		{
			$path = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);
			$file_list = array();

			if (is_dir($path)) {

				if ($dh = opendir($path)) {

					if (substr($path, -1) != DIRECTORY_SEPARATOR) {
						$path .= DIRECTORY_SEPARATOR;
					}

					rewinddir($dh);

					while (($file = readdir($dh)) !== false) {
						if ($file != '.' && $file != '..') {
							$file_path = $path . $file;

							if ($regex == null || preg_match($regex, $file)) {
								$file_list[] = $file_path;
							}

							if ($recurse > 0) {
								$file_list = array_merge($file_list, $this->_get_file_list($file_path, $recurse - 1, $regex));
							}
						}
					}

					closedir($dh);
				}
			}

			return $file_list;
		}

    /**
     * Searches a path for valid module definitions and stores their dependency lists
     * @param string $path
     * @param bool $recurse - note, it will only recurse 1 level in the hierarchy
     */
    function _scan_module_path($path, $recurse = false)
    {
	  	$path = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);
	  	$base = basename($path);
	  	$regex = '/^(?:module|product)\\..*\\.php$/';
    	$result = array();

    	if (is_file($path) && preg_match($regex, $base))
    	{
    		$result[] = $path;
    	}
    	else
    	{
    		$result = $this->_get_file_list($path, $recurse ? 1 : 0, $regex);
    	}

    	if ($result != null)
    	{
    		$scan = array();

    		foreach ($result as $module_path)
    		{
    			$module_dir = basename(dirname($module_path));

    			if (strpos($module_dir, '__') === 0)
    			{
    				continue;
    			}

    			// XXX might be necessary to use fopen/fread for very large module files
    			$module_content = file_get_contents($module_path);
    			$match = null;

    			if (preg_match('/\/(?:\*)+\s*\{\s*(?P<type>Module|Product):\s*(?P<id>[\w-_]+)\s*(?:,\s*Depends:\s*\{(?P<depends>.*)\})?\s*(,\s*Before:\s*\{(?P<before>.*)\})?\s*\}/m', $module_content, $match) > 0)
    			{
    				$module_type = $match['type'];
    				$module_id = $match['id'];
    				$module_deps = isset($match['depends']) ? $match['depends'] : null;
    				$module_before = isset($match['before']) ? $match['before'] : null;
    				$module_info = array('type' => strtolower($module_type), 'id' => $module_id, 'path' => $module_path);

    				if ($module_deps != null)
    				{
    					$module_deps = array_map('trim', explode(',', $module_deps));
    					$module_info['dependency-list'] = $module_deps;
    				}

    				if ($module_before != null)
    				{
    					$module_before = array_map('trim', explode(',', $module_before));
    					$module_info['before-list'] = $module_before;
    				}

    				$scan[$module_id] = $module_info;
    			}
				else die("{$module_path} is not a valid Pope module");
    		}

    		return $scan;
    	}

    	return null;
    }


    /**
     * Loads a module's code according to its dependency list and taking into consideration circular references
     * @param string $module_id
     * @param array $load_path
     */
    function _load_module_internal($module_id, $load_path = null)
    {
    	if ($this->get_module($module_id) != null)
    	{
    		// Module already loaded
    		return true;
    	}

    	if (!is_array($load_path))
    	{
    		$load_path = (array) $load_path;
    	}

    	if (isset($this->_meta_info[$module_id]))
    	{
    		$module_info = $this->_meta_info[$module_id];

    		if (isset($module_info['dependency-list']))
    		{
    			$module_deps = $module_info['dependency-list'];
                        $load_path[] = $module_id;

    			foreach ($module_deps as $module_dep_id)
    			{
    				if (in_array($module_dep_id, $load_path))
    				{
    					// Circular reference
    					continue;
    				}

    				if (!$this->_load_module_internal($module_dep_id, $load_path))
    				{
    					return false;
    				}
    			}
    		}
                if (isset($module_info['path']))
                {
                        $module_path = $module_info['path'];

                        if (is_file($module_path))
                        {
                                include_once($module_path);

                                return true;
                        }
                }
    	}

    	return false;
    }


    /**
     * Private API method. Retrieves the class which currently provides the utility
     * @param string $interface
     * @param string $context
     */
    function _retrieve_utility_class($interface, $context='all')
    {
        $class = FALSE;

        if (!$context) $context = 'all';
        if (isset($this->_utilities[$interface])) {
            if (isset($this->_utilities[$interface][$context])) {
                $class = $this->_utilities[$interface][$context];
            }

            // No utility defined for the specified interface
            else {
                if ($context == 'all') $context = 'default';
                $class = $this->_retrieve_utility_class($interface, FALSE);
                if (!$class)
                    throw new Exception("No utility registered for `{$interface}` with the `{$context}` context.");

            }
        }
        else throw new Exception("No utilities registered for `{$interface}`");

        return $class;
    }
    /**
     * Autoloads any classes, interfaces, or adapters needed by this module
     */
    function _module_autoload($name)
    {
    	if ($this->_module_type_cache == null || count($this->_modules) > $this->_module_type_cache_count)
    	{
    		$this->_module_type_cache_count = count($this->_modules);
      	$modules = $this->_modules;
      	
      	foreach ($modules as $module_id => $module)
      	{
      		$dir = $this->get_module_dir($module_id);
      		$type_list = $module->get_type_list();
      		
      		foreach ($type_list as $type => $filename)
      		{
      			$this->_module_type_cache[strtolower($type)] = $dir . DIRECTORY_SEPARATOR . $filename;
      		}
      	}
    	}
    	
    	$name = strtolower($name);
    	
    	if (isset($this->_module_type_cache[$name]))
    	{
    		$module_filename = $this->_module_type_cache[$name];
    		
    		if (file_exists($module_filename))
    		{
					include_once($module_filename);
    		}
    	}
    }
}
