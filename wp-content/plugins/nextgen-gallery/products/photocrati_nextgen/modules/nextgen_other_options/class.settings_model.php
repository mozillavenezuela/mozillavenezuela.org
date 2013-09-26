<?php

class C_Settings_Model extends C_Component
{
	/**
	 * @var C_NextGen_Settings_Base
	 */
	var $wrapper = NULL;

	static $_instances = array();
	static function get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass;
		}
		return self::$_instances[$context];
	}

	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Validation');
		if ($this->has_context('global') OR $this->has_context('site')) {
			 $this->wrapper = C_NextGen_Settings::get_instance();
		}
		else $this->wrapper = C_NextGen_Settings::get_instance();
	}

	function __get($key)
	{
		return $this->wrapper->get($key);
	}

	function __set($key, $value)
	{
		$this->wrapper->set($key, $value);
		return $this;
	}

	function __isset($key)
	{
		return $this->wrapper->is_set($key);
	}

	function __call($method, $args)
	{
		if (!$this->get_mixin_providing($method)) {
			return call_user_func_array(array(&$this->wrapper, $method), $args);
		}
		else
			return parent::__call($method, $args);

	}
}