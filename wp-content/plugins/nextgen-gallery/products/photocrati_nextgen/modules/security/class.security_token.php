<?php

class Mixin_Security_Token extends Mixin
{
	function get_request_list($args = null)
	{
		return array();
	}
	
	function get_form_html($args = null)
	{
		return null;
	}
	
	function check_request($request_values)
	{
		return false;
	}
	
	function check_current_request()
	{
		return $this->object->check_request($_REQUEST);
	}
}

class Mixin_Security_Token_Property extends Mixin
{
	var $_action_name;
	var $_args;
	
	function init_token($action_name, $args = null)
	{
		$this->object->_action_name = $action_name;
		$this->object->_args = $args;
	}
	
	function get_action_name()
	{
		return $this->object->_action_name;
	}
	
	function get_property($name)
	{
		if (isset($this->object->_args[$name]))
		{
			return $this->object->_args[$name];
		}
		
		return null;
	}
	
	function get_property_list()
	{
		return array_keys((array) $this->object->_args);
	}
}
	
class C_Security_Token extends C_Component
{
	function define($context=FALSE)
	{
		parent::define($context);

		$this->implement('I_Security_Token');
		$this->add_mixin('Mixin_Security_Token');
		$this->add_mixin('Mixin_Security_Token_Property');
	}
}
