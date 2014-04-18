<?php
define('__EXTOBJ_STATIC__', '__STATICALLY_CALLED__');
define('__EXTOBJ_NO_INIT__', '__NO_INIT__');

if (!defined('EXTENSIBLE_OBJECT_ENFORCE_INTERFACES')) {
    define('EXTENSIBLE_OBJECT_ENFORCE_INTERFACES', TRUE);
}


/**
 * Provides helper methods for Pope objects
 */
class PopeHelpers
{
    /**
     * Merges two associative arrays
     * @param array $a1
     * @param array $a2
     * @return array
     */
    function array_merge_assoc($a1, $a2, $skip_empty=FALSE)
    {
		if ($a2) {
			foreach ($a2 as $key => $value) {
				if ($skip_empty && $value === '' OR is_null($value)) continue;
				if (isset($a1[$key])) {

					if (is_array($value)) {
						$a1[$key] = $this->array_merge_assoc($a1[$key], $value);

					}
					else {
						$a1[$key] = $value;
					}

				}
				else $a1[$key] = $value;
			}
		}
		return $a1;
    }


    /**
     * Returns TRUE if a property is empty
     * @param string $var
     * @return boolean
     */
    function is_empty($var, $element=FALSE)
    {
       if (is_array($var) && $element) {
           if (isset($var[$element])) $var = $var[$element];
           else $var = FALSE;
       }

       return (is_null($var) OR (is_string($var) AND strlen($var) == 0) OR $var === FALSE);
    }
}


/**
 * An ExtensibleObject can be extended at runtime with methods from another
 * class.
 *
 * - Mixins may be added or removed at any time during runtime
 * - The path to the mixin is cached so that subsequent method calls are
 *   faster
 * - Pre and post hooks can be added or removed at any time during runtime.
 * - Each method call has a list of associated properties that can be modified
 *   by pre/post hooks, such as: return_value, run_pre_hooks, run_post_hooks, etc
 * - Methods can be replaced by other methods at runtime
 * - Objects can implement interfaces, and are constrained to implement all
 *   methods as defined by the interface
 * - All methods are public. There's no added security by having private/protected
 *   members, as monkeypatching can always expose any method. Instead, protect
 *   your methods using obscurity. Conventionally, use an underscore to define
 *   a method that's private to an API
 */
class ExtensibleObject extends PopeHelpers
{
    const METHOD_PROPERTY_RUN='run';
    const METHOD_PROPERTY_RUN_POST_HOOKS='run_post_hooks';
    const METHOD_PROPERTY_RUN_PRE_HOOKS='run_pre_hooks';
    const METHOD_PROPERTY_RETURN_VALUE='return_value';

    var  $_mixins = array();
    var  $_mixin_priorities = array();
    var  $_pre_hooks = array();
    var  $_global_pre_hooks = array();
    var  $_global_post_hooks= array();
    var  $_post_hooks = array();
    var  $_method_map_cache = array();
    var  $_interfaces = array();
    var  $_overrides = array();
    var  $_aliases = array();
    var  $_method_properties = array();
    var  $_throw_error = TRUE;
    var  $_wrapped_instance = FALSE;
	var	 $object = NULL;
	var  $_disabled_pre_hooks = array();
	var  $_disabled_post_hooks = array();
	var  $_disabled_mixins = array();


    /**
     * Defines a new ExtensibleObject. Any subclass should call this constructor.
     * Subclasses are expected to provide the following:
     * define_instance() - adds extensions which provide instance methods
     * define_class() - adds extensions which provide static methods
     * initialize() - used to initialize the state of the object
     */
    function __construct()
    {
		// Mixins access their parent class by accessing $this->object.
		// Sometimes users mistakenly use $this->object within the parent object
		// itself. As it's becoming a common mistake, we define a $this->object
		// property which points to the current instance (itself)
		$this->object = $this;

        $args = func_get_args();
        $define_instance = TRUE;
        $init_instance = TRUE;

        // The first argument could be a flag to ExtensibleObject
        // which indicates that only static-like methods will be called
        if (count($args) >= 1) {
            $first_arg = $args[0];
            if (is_string($first_arg)) {
            	switch ($first_arg) {
            		case __EXTOBJ_STATIC__:
            		{
		              $define_instance = FALSE;
		              $init_instance = FALSE;

		              if (method_exists($this, 'define_class')) {
						  $this->call_callback($this, 'define_class', $args);
		              }
		              elseif (method_exists($this, 'define_static')) {
						  $this->call_callback($this, 'define_static', $args);
		              }

					  break;
            		}
            		case __EXTOBJ_NO_INIT__:
            		{
		              $init_instance = FALSE;

            			break;
            		}
            	}
            }
        }

        // Are we to define instance methods?
        if ($define_instance)
        {
            if (method_exists($this, 'define_instance'))
            {
                $reflection = new ReflectionMethod($this, 'define_instance');
                $reflection->invokeArgs($this, $args);
                // call_user_func_array(array($this, 'define_instance'), $args);
            }
            elseif (method_exists($this, 'define')) {
                $reflection = new ReflectionMethod($this, 'define');
                $reflection->invokeArgs($this, $args);
                // call_user_func_array(array($this, 'define'), $args);
            }

            if (EXTENSIBLE_OBJECT_ENFORCE_INTERFACES) $this->_enforce_interface_contracts();

			if ($init_instance)
            {
                // Initialize the state of the object
                if (method_exists($this, 'initialize')) {
                    $reflection = new ReflectionMethod($this, 'initialize');
                    $reflection->invokeArgs($this, $args);
                    // call_user_func_array(array($this, 'initialize'), $args);
                }
            }
        }
    }

	/**
	 * Disabled prehooks for a particular method
	 * @param string $method
	 */
	function disable_pre_hooks($method)
	{
		$this->_disabled_pre_hooks[] = $method;
		return $this;
	}


	/**
	 * Enable prehooks for a particular method
	 * @param string $method
	 */
	function enable_pre_hooks($method)
	{
		$index = array_search($method, $this->_disabled_pre_hooks);
		if ($index !== FALSE) {
			unset($this->_disabled_pre_hooks[$index]);
		}
		return $this;
	}

	/**
	 * Disabled posthooks for a particular method
	 * @param string $method
	 */
	function disable_post_hooks($method)
	{
		$this->_disabled_post_hooks[] = $method;
		return $this;
	}


	/**
	 * Enable post-hooks for a particular method
	 * @param string $method
	 */
	function enable_post_hooks($method)
	{
		$index = array_search($method, $this->_disabled_post_hooks);
		if ($index !== FALSE) {
			unset($this->_disabled_post_hooks[$index]);
		}
		return $this;
	}

	/**
	 * Determines if post hooks are enabled for a particular method
	 * @param string $method
	 * @return bool
	 */
	function are_post_hooks_enabled($method)
	{
		return !empty($this->_post_hooks) && (!in_array($method, $this->_disabled_post_hooks));
	}


	/**
	 * Determines if pre hooks are enabled for a particular method
	 * @param string $method
	 * @return bool
	 */
	function are_pre_hooks_enabled($method)
	{
		return !empty($this->_pre_hooks) && (!in_array($method, $this->_disabled_pre_hooks));
	}


    /**
     * Adds an extension class to the object. The extension provides
     * methods for this class to expose as it's own
     * @param string $class
     */
    function add_mixin($class, $instantiate=FALSE)
    {
		$retval = TRUE;

		if (!$this->has_mixin($class)) {
			// We used to instantiate the class, but I figure
			// we might as well wait till the method is called to
			// save memory. Instead, the _call() method calls the
			// _instantiate_mixin() method below.
			$this->_mixins[$class] = FALSE; // new $class();
			array_unshift($this->_mixin_priorities, $class);
			$this->_flush_cache();

			// Should we instantiate the object now?
			if ($instantiate) $this->_instantiate_mixin($class);
		}
		else $retval = FALSE;

		return $retval;
    }


	/**
	 * Determines if a mixin has been added to this class
	 * @param string $klass
	 * @return bool
	 */
	function has_mixin($klass)
	{
		return (isset($this->_mixins[$klass]));
	}


    /**
     * Stores the instantiated class
     * @param string $class
     * @return mixed
     */
    function _instantiate_mixin($class)
    {
        $retval = FALSE;
        if ($this->_mixins[$class])
            $retval = $this->_mixins[$class];
        else {
            $obj= new $class();
            $obj->object = &$this;
            $retval = $this->_mixins[$class] = &$obj;
            if (method_exists($obj, 'initialize')) $obj->initialize();
        }


        return $retval;
    }


    /**
     * Deletes an extension from the object. The methods provided by that
     * extension are no longer available for the object
     * @param string $class
     */
    function del_mixin($class)
    {
        unset($this->_mixins[$class]);
        $index = array_search($class, $this->_mixin_priorities);
        if ($index !== FALSE) {
            unset($this->_mixin_priorities[$index]);
			foreach ($this->_disabled_mixins as $method => $disabled_mixins) {
				$index = array_search($class, $disabled_mixins);
				if (is_int($index)) unset($this->_disabled_mixins[$method][$index]);
			}
            $this->_flush_cache();
        }

    }


    function remove_mixin($class)
    {
        $this->del_mixin($class);
    }


    /**
     * Replaces an extension methods with that of another class.
     * @param string $method
     * @param string $class
     * @param string $new_method
     */
    function replace_method($method, $class, $new_method=FALSE)
    {
        if (!$new_method) $new_method = $method;
        $this->_overrides[$method] = $class;
        $this->add_pre_hook($method, "replacement_{$method}_{$class}_{$new_method}", $class, $new_method);
        $this->_flush_cache();

    }


    /**
     * Restores a method that was replaced by a former call to replace_method()
     * @param string $method
     */
    function restore_method($method)
    {
        $class = $this->_overrides[$method];
        unset($this->_overrides[$method]);
        $this->del_pre_hook($method, $class);
        $this->_flush_cache();
    }


	/**
	 * Returns the Mixin which provides the specified method
	 * @param string $method
	 */
	function get_mixin_providing($method, $return_obj=FALSE)
	{
		$retval = FALSE;

		// If it's cached, then we've got it easy
		if ($this->is_cached($method)) {

			$object = $this->_method_map_cache[$method];
			$retval = get_class($object);
		}

		// Otherwise, we have to look it up
		else {
            foreach ($this->get_mixin_priorities($method) as $klass) {
                $object = $this->_instantiate_mixin($klass);
                if (method_exists($object, $method)) {
                    $retval = $return_obj ? $object : get_class($object);
                    $this->_cache_method($object, $method);
                    break;
                }
            }
		}

		return $retval;
	}


    /**
     * When an ExtensibleObject is instantiated, it checks whether all
     * the registered extensions combined provide the implementation as required
     * by the interfaces registered for this object
     */
    function _enforce_interface_contracts()
    {
        $errors = array();

        foreach ($this->_interfaces as $i) {
            $r = new ReflectionClass($i);
            foreach ($r->getMethods() as $m) {
                if (!$this->has_method($m->name)) {
					$klass = $this->get_class_name($this);
                    $errors[] = "`{$klass}` does not implement `{$m->name}` as required by `{$i}`";
                }
            }
        }

        if ($errors) throw new Exception(implode(". ", $errors));
    }


    /**
     * Implement a defined interface. Does the same as the 'implements' keyword
     * for PHP, except this method takes into account extensions
     * @param string $interface
     */
    function implement($interface)
    {
        $this->_interfaces[] = $interface;
    }


    /**
     * Adds a hook that gets executed before every method call
     * @param string $name
     * @param string $class
     * @param string $hook_method
     */
    function add_global_pre_hook($name, $class, $hook_method)
    {
        $this->add_pre_hook('*', $name, $class, $hook_method);
    }

    /**
     * Adds a hook that gets executed after every method call
     *
     * @param string $name
     * @param string $class
     * @param string $hook_method
     */
    function add_global_post_hook($name, $class, $hook_method)
    {
        $this->add_pre_hook('*', $name, $class, $hook_method);
    }


    /**
     * Adds a hook that will get executed before a particular method call
     * @param string $method
     * @param string $name
     * @param string $class
     * @param string $hook_method
     */
    function add_pre_hook($method, $name, $class, $hook_method=FALSE)
    {
        if (!$hook_method) $hook_method = $method;

        // Is this a global pre hook?
        if ($method == '*') {
            $this->_global_pre_hooks[$name] = array(
                new $class,
                $hook_method
            );
        }

        // This is a method-specific pre hook
        else {
            if (!isset($this->_pre_hooks[$method])) {
                $this->_pre_hooks[$method] = array();
            }

            $this->_pre_hooks[$method][$name] = array(
                new $class,
                $hook_method
            );
        }
    }


    /**
     * Adds a hook to be called after a particular method call
     * @param string $method
     * @param string $hook_name
     * @param string $class
     * @param string $hook_method
     */
    function add_post_hook($method, $hook_name, $class, $hook_method=FALSE)
    {
        // Is this a global post hook?
        if ($method == '*') {
            $this->_post_hooks[$hook_name] = array(
              new $class,
                $hook_method
            );
        }

        // This is a method-specific post hook
        else {
            if (!$hook_method) $hook_method = $method;

            if (!isset($this->_post_hooks[$method])) {
                $this->_post_hooks[$method] = array();
            }

            $this->_post_hooks[$method][$hook_name] = array(
                new $class,
                $hook_method
            );
        }
    }


    /**
     * Deletes a hook that's executed before the specified method
     * @param string $method
     * @param string $name
     */
    function del_pre_hook($method, $name)
    {

        unset($this->_pre_hooks[$method][$name]);
    }

    /**
     * Deletes all pre hooks registered
    **/
    function del_pre_hooks($method=FALSE)
    {
        if (!$method)
            $this->_pre_hooks = array();
        else
            unset($this->_pre_hooks[$method]);
    }


    /**
     * Deletes a hook that's executed after the specified method
     * @param string $method
     * @param string $name
     */
    function del_post_hook($method, $name)
    {
        unset($this->_post_hooks[$method][$name]);
    }

    /**
     * Deletes all post hooks
     */
    function del_post_hooks($method=FALSE)
    {
        if (!$method)
            $this->_post_hooks = array();
        else
            unset($this->_post_hooks[$method]);
    }


    /**
     * Wraps a class within an ExtensibleObject class.
     * @param string $klass
     * @param array callback, used to tell ExtensibleObject how to instantiate
     * the wrapped class
     */
    function wrap($klass, $callback=FALSE, $args=array())
    {
        if ($callback) {
            $this->_wrapped_instance = call_user_func($callback, $args);
        }
        else {
            $this->_wrapped_instance = new $klass();
        }
    }


    /**
     * Determines if the ExtensibleObject is a wrapper for an existing class
     */
    function is_wrapper()
    {
        return $this->_wrapped_instance ? TRUE : FALSE;
    }


    /**
     * Returns the name of the class which this ExtensibleObject wraps
     * @return object
     */
    function &get_wrapped_instance()
    {
        return $this->_wrapped_instance;
    }


    /**
     * Returns TRUE if the wrapped class provides the specified method
     */
    function wrapped_class_provides($method)
    {
        $retval = FALSE;

        // Determine if the wrapped class is another ExtensibleObject
        if (method_exists($this->_wrapped_instance, 'has_method')) {
			$retval = $this->_wrapped_instance->has_method($method);
        }
        elseif (method_exists($this->_wrapped_instance, $method)){
            $retval = TRUE;
        }

        return $retval;
    }


    /**
     * Provides a means of calling static methods, provided by extensions
     * @param string $method
     * @return mixed
     */
    static function get_class()
    {
		// Note: this function is static so $this is not defined
        $klass = self::get_class_name();
        $obj = new $klass(__EXTOBJ_STATIC__);
        return $obj;
    }


	/**
	 * Gets the name of the ExtensibleObject
	 * @return string
	 */
	static function get_class_name($obj = null)
	{
		if ($obj)
			return get_class($obj);
		elseif (function_exists('get_called_class'))
			return get_called_class();
		else
			return get_class();
	}

	/**
     * Gets a property from a wrapped object
     * @param string $property
     * @return mixed
     */
    function &__get($property)
    {
		$retval = NULL;
        if ($this->is_wrapper()) {
			try {
				$reflected_prop = new ReflectionProperty($this->_wrapped_instance, $property);

				// setAccessible method is only available for PHP 5.3 and above
				if (method_exists($reflected_prop, 'setAccessible')) {
					$reflected_prop->setAccessible(TRUE);
				}

				$retval = $reflected_prop->getValue($this->_wrapped_instance);
			}
			catch (ReflectionException $ex)
			{
				$retval = $this->_wrapped_instance->$property;
			}
        }

		return $retval;
    }

	/**
	 * Determines if a property (dynamic or not) exists for the object
	 * @param string $property
	 * @return boolean
	 */
	function __isset($property)
	{
		$retval = FALSE;

		if (property_exists($this, $property)) {
			$retval = isset($this->$property);
		}
		elseif ($this->is_wrapper() && property_exists($this->_wrapped_instance, $property)) {
			$retval = isset($this->$property);
		}

		return $retval;
	}


    /**
     * Sets a property on a wrapped object
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    function &__set($property, $value)
    {
		$retval = NULL;
        if ($this->is_wrapper()) {
			try {
				$reflected_prop = new ReflectionProperty($this->_wrapped_instance, $property);

				// The property must be accessible, but this is only available
				// on PHP 5.3 and above
				if (method_exists($reflected_prop, 'setAccessible')) {
					$reflected_prop->setAccessible(TRUE);
				}

				$retval = &$reflected_prop->setValue($this->_wrapped_instance, $value);
			}

			// Sometimes reflection can fail. In that case, we need
			// some ingenuity as a failback
			catch (ReflectionException $ex) {
				$this->_wrapped_instance->$property = $value;
				$retval = &$this->_wrapped_instance->$property;
			}

        }
		else {
			$this->$property = $value;
			$retval = &$this->$property;
		}
        return $retval;
    }


    /**
     * Finds a method defined by an extension and calls it. However, execution
     * is a little more in-depth:
     * 1) Execute all global pre-hooks and any pre-hooks specific to the requested
     *    method. Each method call has instance properties that can be set by
     *    other hooks to modify the execution. For example, a pre hook can
     *    change the 'run_pre_hooks' property to be false, which will ensure that
     *    all other pre hooks will NOT be executed.
     * 2) Runs the method. Checks whether the path to the method has been cached
     * 3) Execute all global post-hooks and any post-hooks specific to the
     *    requested method. Post hooks can access method properties as well. A
     *    common usecase is to return the value of a post hook instead of the
     *    actual method call. To do this, set the 'return_value' property.
     * @param string $method
     * @param array $args
     * @return mixed
     */
    function __call($method, $args)
    {
        $this->reset_method_properties($method, $args);

        // Run pre hooks?
        if ($this->are_pre_hooks_enabled($method) && $this->get_method_property($method, self::METHOD_PROPERTY_RUN_PRE_HOOKS)) {

            // Combine global and method-specific pre hooks
            $prehooks = $this->_global_pre_hooks;
            if (isset($this->_pre_hooks[$method])) {
                $prehooks = array_merge($prehooks, $this->_pre_hooks[$method]);
            }

            // Apply each hook
            foreach ($prehooks as $hook_name => $hook) {
				$method_args = $this->get_method_property($method, 'arguments', $args);
                $this->_run_prehook(
					$hook_name,
					$method,
					$hook[0],
					$hook[1],
					$method_args
				);
            }
        }

        // Are we to run the actual method? A pre hook might have told us
        // not to
        if ($this->get_method_property($method, self::METHOD_PROPERTY_RUN) && !isset($this->_overrides[$method]))
        {
            if (($this->get_mixin_providing($method))) {
                $this->set_method_property(
                    $method,
                    self::METHOD_PROPERTY_RETURN_VALUE,
                    $this->_exec_cached_method($method, $this->get_method_property($method, 'arguments'))
                );
            }

            // This is NOT a wrapped class, and no extensions provide the method
            else {
                // Perhaps this is a wrapper and the wrapped object
                // provides this method
                if ($this->is_wrapper() && $this->wrapped_class_provides($method))
                {
                    $object = $this->add_wrapped_instance_method($method);
                    $this->set_method_property(
                        $method,
                        self::METHOD_PROPERTY_RETURN_VALUE,
                        call_user_func_array(
                            array(&$object, $method),
                            $this->get_method_property($method, 'arguments')
                        )
                    );
                }
                elseif ($this->_throw_error) {
                    throw new Exception("`{$method}` not defined for " . get_class());
                }
                else {
                    return FALSE;
                }
            }
        }

        // Are we to run post hooks? A pre hook might have told us not to
        if ($this->are_post_hooks_enabled($method) && $this->get_method_property($method, self::METHOD_PROPERTY_RUN_POST_HOOKS)) {

            // Combine global and method-specific post hooks
            $posthooks = $this->_global_post_hooks;
            if (isset($this->_post_hooks[$method])) {
                $posthooks = array_merge($posthooks, $this->_post_hooks[$method]);
            }

            // Apply each hook
            foreach ($posthooks as $hook_name => $hook) {
				$method_args = $this->get_method_property($method, 'arguments', $args);
                $this->_run_post_hook(
					$hook_name,
					$method,
					$hook[0],
					$hook[1],
					$method_args
				);
            }
        }

		// Get return value, clear all method properties, and then return
        $retval = $this->get_method_property($method, self::METHOD_PROPERTY_RETURN_VALUE);
		$this->remove_method_properties($method);
		return $retval;
    }


	/**
	 * Adds the implementation of a wrapped instance method to the ExtensibleObject
	 * @param string $method
	 * @return Mixin
	 */
	function add_wrapped_instance_method($method)
	{
		$retval = $this->get_wrapped_instance();

		// If the wrapped instance is an ExtensibleObject, then we don't need
		// to use reflection
		if (!is_subclass_of($this->get_wrapped_instance(), 'ExtensibleObject')) {
			$func	= new ReflectionMethod($this->get_wrapped_instance(), $method);

			// Get the entire method definition
			$filename = $func->getFileName();
			$start_line = $func->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
			$end_line = $func->getEndLine();
			$length = $end_line - $start_line;
			$source = file($filename);
			$body = implode("", array_slice($source, $start_line, $length));
            $body = preg_replace("/^\s{0,}private|protected\s{0,}/", '', $body);

			// Change the context
			$body = str_replace('$this', '$this->object', $body);
			$body = str_replace('$this->object->object', '$this->object', $body);
			$body = str_replace('$this->object->$', '$this->object->', $body);

			// Define method for mixin
			$wrapped_klass = get_class($this->get_wrapped_instance());
			$mixin_klass = "Mixin_AutoGen_{$wrapped_klass}_{$method}";
			if (!class_exists($mixin_klass)) {
				eval("class {$mixin_klass} extends Mixin{
					{$body}
				}");
			}
			$this->add_mixin($mixin_klass);
			$retval = $this->_instantiate_mixin($mixin_klass);
			$this->_cache_method($retval, $method);

		}

		return $retval;
	}


    /**
     * Provides an alternative way to call methods
     */
    function call_method($method, $args=array())
    {
        if (method_exists($this, $method))
        {
            $reflection = new ReflectionMethod($this, $method);
            return $reflection->invokeArgs($this, array($args));
        }
        else {
            return $this->__call($method, $args);
        }
    }


    /**
     * Returns TRUE if the method in particular has been cached
     * @param string $method
     * @return type
     */
    function is_cached($method)
    {
        return isset($this->_method_map_cache[$method]);
    }


    /**
     * Caches the path to the extension which provides a particular method
     * @param string $object
     * @param string $method
     */
    function _cache_method($object, $method)
    {
        $this->_method_map_cache[$method] = $object;
    }


	/**
	 * Gets a list of mixins by their priority, excluding disabled mixins
	 * @param string $method
	 * @return array
	 */
	function get_mixin_priorities($method)
	{
		$retval = array();
		foreach ($this->_mixin_priorities as $mixin) {
			if ($this->is_mixin_disabled($method, $mixin))
                continue;
			$retval[] = $mixin;
		}
		return $retval;
	}


	/**
	 * Determines if a mixin is disabled for a particular method
	 * @param string $method
	 * @param string $mixin
	 * @return boolean
	 */
	function is_mixin_disabled($method, $mixin)
	{
		$retval = FALSE;
		if (isset($this->_disabled_mixins[$method]))
			if (in_array($mixin, $this->_disabled_mixins[$method]) !== FALSE)
				$retval = TRUE;
		return $retval;
	}


    /**
     * Flushes the method cache
     */
    function _flush_cache()
    {
        $this->_method_map_cache = array();
    }


    /**
     * Returns TRUE if the object provides the particular method
     * @param string $method
     * @return boolean
     */
    function has_method($method)
    {
        $retval = FALSE;

        // Have we looked up this method before successfully?
        if ($this->is_cached($method)) {
            $retval = TRUE;
        }

        // Is this a local PHP method?
        elseif (method_exists($this, $method)) {
            $retval = TRUE;
        }

        // Is a mixin providing this method
        elseif ($this->get_mixin_providing($method)) {
            $retval = TRUE;
        }

        elseif ($this->is_wrapper() && $this->wrapped_class_provides($method)) {
            $retval = TRUE;
        }

        return $retval;
    }


    /**
     * Runs a particular pre hook for the specified method. The return value
     * is assigned to the "[hook_name]_prehook_retval" method property
     * @param string $hook_name
     * @param string $method_called
     * @param Ext $object
     * @param string $hook_method
     *
     */
    function _run_prehook($hook_name, $method_called, $object, $hook_method, &$args)
    {
        $object->object = &$this;
        $object->method_called = $method_called;

        // Are we STILL to execute pre hooks? A pre-executed hook might have changed this
        if ($this->get_method_property($method_called, 'run_pre_hooks'))
        {
            $reflection = new ReflectionMethod($object, $hook_method);
            $this->set_method_property(
                $method_called,
                $hook_name . '_prehook_retval',
                $reflection->invokeArgs($object, $args)
            );
        }
    }

    /**
     * Runs the specified post hook for the specified method
     * @param string $hook_name
     * @param string $method_called
     * @param Ext $object
     * @param string $hook_method
     */
    function _run_post_hook($hook_name, $method_called, $object, $hook_method, &$args)
    {
        $object->object = &$this;
        $object->method_called = $method_called;

        // Are we STILL to execute post hooks? A post-executed hook might have changed this
        if ($this->get_method_property($method_called, 'run_post_hooks'))
        {
            $reflection = new ReflectionMethod($object, $hook_method);
            $this->set_method_property(
                $method_called,
                $hook_name . '_post_hook_retval',
                $reflection->invokeArgs($object, $args)
            );
        }
    }

    /**
     * Returns TRUE if a pre-hook has been registered for the specified method
     * @param string $method
     * @return boolean
     */
    function have_prehook_for($method, $name = null)
    {
        if (is_null($name)) {
            return isset($this->_pre_hooks[$method]);
        } else {
            return isset($this->_pre_hooks[$method][$name]);
        }

    }


    /**
     * Returns TRUE if a posthook has been registered for the specified method
     * @param string $method
     * @return boolean
     */
    function have_posthook_for($method, $name = null)
    {
        $retval = FALSE;

        if (isset($this->_post_hooks[$method])) {
            if (!$name) $retval = TRUE;
            else $retval = isset($this->_post_hooks[$method][$name]);
        }

        return $retval;
    }

	/**
	 * Disables a mixin for a particular method. This ensures that even though
	 * mixin provides a particular method, it won't be used to provide the
	 * implementation
	 * @param string $method
	 * @param string $klass
	 */
	function disable_mixin($method, $klass)
	{
		unset($this->_method_map_cache[$method]);
		if (!isset($this->_disabled_mixins[$method])) {
			$this->_disabled_mixins[$method] = array();
		}
		$this->_disabled_mixins[$method][] = $klass;
	}


	/**
	 * Enable a mixin for a particular method, that was previously disabled
	 * @param string $method
	 * @param string $klass
	 */
	function enable_mixin($method, $klass)
	{
		unset($this->_method_map_cache[$method]);
		if (isset($this->_disabled_mixins[$method])) {
			$index = array_search($klass, $this->_disabled_mixins[$method]);
			if ($index !== FALSE) unset($this->_disabled_mixins[$method][$index]);
		}
	}


	/**
	 * Gets a list of mixins that are currently disabled for a particular method
	 * @see disable_mixin()
	 * @param string $method
	 * @return array
	 */
	function get_disabled_mixins_for($method)
	{
		$retval = array();
		if (isset($this->_disabled_mixins[$method])) {
			$retval =  $this->_disabled_mixins[$method];
		}
		return $retval;
	}


    /**
     * Executes a cached method
     * @param string $method
     * @param array $args
     * @return mixed
     */
    function _exec_cached_method($method, $args=array())
    {
        $object = $this->_method_map_cache[$method];
        $object->object = &$this;
        $reflection = new ReflectionMethod($object, $method);
        return $reflection->invokeArgs($object, $args);
    }


    /**
     * Sets the value of a method property
     * @param string $method
     * @param string $property
     * @param mixed $value
     */
    function set_method_property($method, $property, $value)
    {
        if (!isset($this->_method_properties[$method])) {
            $this->_method_properties[$method] = array();
        }

        return $this->_method_properties[$method][$property] = $value;
    }


    /**
     * Gets the value of a method property
     * @param string $method
     * @param string $property
     */
    function get_method_property($method, $property, $default=NULL)
    {
        $retval = NULL;

        if (isset($this->_method_properties[$method][$property])) {
            $retval = $this->_method_properties[$method][$property];
        }

		if (is_null($retval)) $retval=$default;

        return $retval;
    }


    /**
     * Clears all method properties to have their default values. This is called
     * before every method call (before pre-hooks)
     * @param string $method
     */
    function reset_method_properties($method, $args=array())
    {
        $this->_method_properties[$method] = array(
            'run'               => TRUE,
            'run_pre_hooks'     => TRUE,
            'run_post_hooks'    => TRUE,
			'arguments'			=> $args
        );
    }

	/**
	 * Removes the cache of the method properties
	 * @param $method
	 */
	function remove_method_properties($method)
	{
		unset($this->_method_properties[$method]);
	}

	/**
	 * Gets all method properties
	 * @return array
	 */
	function get_method_properties($method)
	{
		return $this->_method_properties[$method];
	}

	/**
	 * Sets all method properties
	 * @param $method
	 * @param $props
	 */
	function set_method_properties($method, $props)
	{
		foreach ($props as $key => $value) {
			$this->set_method_property($method, $key, $value);
		}
	}

    /**
     * Returns TRUE if the ExtensibleObject has decided to implement a
     * particular interface
     * @param string $interface
     * @return boolean
     */
    function implements_interface($interface)
    {
        return in_array($interface, $this->_interfaces);
    }

    function get_class_definition_dir($parent=FALSE)
    {
        return dirname($this->get_class_definition_file($parent));
    }

    function get_class_definition_file($parent=FALSE)
    {
		$klass = $this->get_class_name($this);
        $r = new ReflectionClass($klass);
        if ($parent) {
            $parent = $r->getParentClass();
            return $parent->getFileName();
        }
        return $r->getFileName();
    }

    /**
     * Returns get_class_methods() optionally limited by Mixin
     *
     * @param string (optional) Only show functions provided by a mixin
     * @return array Results from get_class_methods()
     */
    public function get_instance_methods($name = null)
    {
        if (is_string($name))
        {
            $methods = array();
            foreach ($this->_method_map_cache as $method => $mixin) {
                if ($name == get_class($mixin))
                {
                    $methods[] = $method;
                }
            }
            return $methods;
        } else {
            $methods = get_class_methods($this);
            foreach ($this->_mixins as $mixin) {
                $methods = array_unique(array_merge($methods, get_class_methods($mixin)));
                sort($methods);
            }

            return $methods;
        }
    }
}


/**
 * An mixin provides methods for an ExtensibleObject to use
 */
class Mixin extends PopeHelpers
{
    /**
     * The ExtensibleObject which called the extension's method
     * @var ExtensibleObject
     */
    var $object;

    /**
     * The name of the method called on the ExtensibleObject
     * @var type
     */
    var $method_called;

    /**
     * There really isn't any concept of 'parent' method. An ExtensibleObject
     * instance contains an ordered array of extension classes, which provides
     * the method implementations for the instance to use. Suppose that an
     * ExtensibleObject has two extension, and both have the same methods.The
     * last extension appears to 'override' the first extension. So, instead of calling
     * a 'parent' method, we're actually just calling an extension that was added sooner than
     * the one that is providing the current method implementation.
     */
    function call_parent($method)
    {
        $retval = NULL;

        // To simulate a 'parent' call, we remove the current extension from the
        // ExtensibleObject that is providing the method's implementation, re-emit
        // the call on the instance to trigger the implementation from the previously
        // added extension, and then restore things by re-adding the current extension.
        // It's complicated, but it works.

        // We need to determine the name of the extension. Because PHP 5.2 is
        // missing get_called_class(), we have to look it up in the backtrace
        $backtrace = debug_backtrace();
        $klass = get_class($backtrace[0]['object']);

		// Get the method properties. We'll store this afterwards.
		$props = $this->object->get_method_properties($method);

		// Perform the routine described above...
		$this->object->disable_pre_hooks($method);
		$this->object->disable_post_hooks($method);
		$this->object->disable_mixin($method, $klass);

        // Call anchor
        $args = func_get_args();

        // Remove $method parameter
        array_shift($args);
        $retval = $this->object->call_method($method, $args);

		// Re-enable hooks
		$this->object->enable_pre_hooks($method);
		$this->object->enable_post_hooks($method);
		$this->object->enable_mixin($method, $klass);

		// Re-set all method properties
		$this->object->set_method_properties($method, $props);

        return $retval;
    }

    /**
     * Although is is preferrable to call $this->object->method(), sometimes
     * it's nice to use $this->method() instead.
     * @param string $method
     * @param array $args
     * @return mixed
     */
    function __call($method, $args)
    {
        if ($this->object->has_method($method)) {
            return call_user_func_array(array(&$this->object, $method), $args);
        }
    }

    /**
     * Although extensions can have state, it's probably more desirable to maintain
     * the state in the parent object to keep a sane environment
     * @param string $property
     * @return mixed
     */
    function __get($property)
    {
        return $this->object->$property;
    }
}

/**
 * An extension which has the purpose of being used as a hook
 */
class Hook extends Mixin
{
    // Similiar to a mixin's call_parent method.
    // If a hook needs to call the method that it applied the
    // Hook n' Anchor pattern to, then this method should be called
    function call_anchor()
    {
		// Disable hooks, so that we call the anchor point
		$this->object->disable_pre_hooks($this->method_called);
		$this->object->disable_post_hooks($this->method_called);

        // Call anchor
        $args = func_get_args();
        $retval = $this->object->call_method($this->method_called, $args);

		// Re-enable hooks
		$this->object->enable_pre_hooks($this->method_called);
		$this->object->enable_post_hooks($this->method_called);

		return $retval;
    }

    /**
     * Provides an alias for call_anchor, as there's no parent
     * to call in the context of a hook.
     */
    function call_parent($method)
    {
        $args = func_get_args();
        return call_user_func_array(
            array(&$this, 'call_anchor'),
            $args
        );
    }
};