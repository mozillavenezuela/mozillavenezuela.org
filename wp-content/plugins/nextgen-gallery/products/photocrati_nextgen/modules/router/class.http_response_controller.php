<?php

class C_Http_Response_Controller extends C_MVC_Controller
{
	static $_instances = array();

	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Http_Response_Actions');
		$this->implement('I_Http_Response');
	}

	static function get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}
}

class Mixin_Http_Response_Actions extends Mixin
{
	function http_301_action()
	{
		header('HTTP/1.1 301 Permanent Redirect');
		header("Location: {$this->object->get_routed_url()}");
	}

	function http_302_action()
	{
		header('HTTP/1.1 302 Temporary Redirect');
		header("Location: {$this->object->get_routed_url()}");
	}

	function http_500_action()
	{

	}

	function http_404_action()
	{

	}
}
