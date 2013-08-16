<?php

if (!class_exists('C_Photocrati_Settings_Manager_Base')) {
	/**
	 * Provides a base abstraction for a Settings Manager
	 * Class C_Settings_Manager_Base
	 */
	abstract class C_Photocrati_Settings_Manager_Base implements ArrayAccess
	{
		static $option_name		= 'pope_settings';
		protected $_options		= array();
		protected $_defaults	= array();

		abstract function save();
		abstract function destroy();
		abstract function load();

		protected function __construct()
		{
			$this->load();
		}

		/**
		 * Gets the value of a particular setting
		 * @param $key
		 * @param null $default
		 * @return null
		 */
		function get($key, $default=NULL)
		{
			$retval = $default;

			if (isset($this->_options[$key]))
				$retval =  $this->_options[$key];

			// In case a stdObject has been passed in as a value, we
			// want to only return scalar values or arrays
			if (is_object($retval)) $retval = (array) $retval;

			return $retval;
		}

		/**
		 * Sets a setting to a particular value
		 * @param string $key
		 * @param mixed $value
		 * @return mixed
		 */
		function set($key, $value=NULL)
		{
			if (is_object($value)) $value = (array) $value;

			if (is_array($key)) {
				foreach ($key as $k=>$v) $this->set($k, $v);
			}
			else $this->_options[$key] = $value;

			return $this;
		}

		/**
		 * Deletes a setting
		 * @param string $key
		 */
		function delete($key)
		{
			unset($this->_options[$key]);
		}

		/**
		 * Determines if a setting exists or not
		 * @param $key
		 * @return bool
		 */
		function is_set($key)
		{
			return array_key_exists($key, $this->_options);
		}

		/**
		 * Alias to is_set()
		 * @param $key
		 * @return bool
		 */
		function exists($key)
		{
			return $this->is_set($key);
		}

		function does_not_exist($key)
		{
			return !$this->exists($key);
		}

		function reset()
		{
			$this->_options = array();
		}

		/**
		 * This function does two things:
		 * a) If a value hasn't been set for the specified key, or it's been set to a previously set
		 *    default value, then set this key to the value specified
		 * b) Sets a new default value for this key
		 */
		function set_default_value($key, $default)
		{
			if (!isset($this->_defaults[$key])) $this->_defaults[$key] = $default;
			if (is_null($this->get($key, NULL)) OR $this->get($key) == $this->_defaults[$key]) {
				$this->set($key, $default);
			}
			$this->_defaults[$key] = $default;
			return $this->get($key);
		}

		function offsetExists($key)
		{
			return $this->is_set($key);
		}

		function offsetGet($key)
		{
			return $this->get($key);
		}

		function offsetSet($key, $value)
		{
			return $this->set($key, $value);
		}

		function offsetUnset($key)
		{
			return $this->delete($key);
		}

		function __get($key)
		{
			return $this->get($key);
		}

		function __set($key, $value)
		{
			return $this->set($key, $value);
		}

		function __isset($key)
		{
			return $this->is_set($key);
		}

		function __toString()
		{
			return json_encode($this->_options);
		}

		function __toArray()
		{
			return $this->_options;
		}

		function to_array()
		{
			return $this->__toArray();
		}

		function to_json()
		{
			return json_encode($this->_options);
		}

		function from_json($json)
		{
			$this->_options = (array)json_decode($json);
		}
	}
}

if (!class_exists('C_Photocrati_Global_Settings_Manager')) {
	class C_Photocrati_Global_Settings_Manager extends C_Photocrati_Settings_Manager_Base
	{
		public static function get_instance()
		{
			static $_instance = NULL;
			if (is_null($_instance)) {
				$klass = get_class();
				$_instance = new $klass();
			}
			return $_instance;
		}

		function save()
		{
			return update_site_option(self::$option_name, $this->to_array());
		}

		function load()
		{
			$this->_options = get_site_option(self::$option_name, $this->to_array());
			if (!$this->_options) $this->_options = array();
		}

		function destroy()
		{
			return delete_site_option(self::$option_name);
		}
	}
}


if (!class_exists('C_Photocrati_Settings_Manager')) {
	class C_Photocrati_Settings_Manager extends C_Photocrati_Settings_Manager_Base
	{
		public static function get_instance()
		{
			static $_instance = NULL;
			if (is_null($_instance)) {
				$klass = get_class();
				$_instance = new $klass();
			}
			return $_instance;
		}

		function get($key, $default=NULL)
		{
			$retval = parent::get($key, NULL);

			if (is_null($retval)) {
				$retval = C_Photocrati_Global_Settings_Manager::get_instance()->get($key, $default);
			}
			return $retval;
		}

		function save()
		{
			return update_option(self::$option_name, $this->to_array());
		}

		function load()
		{
			$this->_options = get_option(self::$option_name, array());
		}

		function destroy()
		{
			delete_option(self::$option_name);
		}


	}
}

