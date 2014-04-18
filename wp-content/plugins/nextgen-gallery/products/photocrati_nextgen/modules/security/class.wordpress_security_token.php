<?php

class Mixin_Wordpress_Security_Token extends Mixin
{
	function get_request_list($args = null)
	{
		$prefix = isset($args['prefix']) ? $args['prefix'] : null;
		$action_name = $this->object->get_action_name();
		$list = array();
		
		if ($prefix != null)
		{
			$list[$action_name . '_prefix'] = $prefix;
		}
		
		$action = $this->object->get_nonce_name();
		$list[$prefix . $action_name . '_sec'] = wp_create_nonce($action);
		
		return $list;
	}
	
	function get_form_html($args = null)
	{
		$list = $this->object->get_request_list($args);
		$out = null;
		
		foreach ($list as $name => $value)
		{
			$out .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
		}
		
		return $out;
	}
	
	function get_json($args = null)
	{
		$list = $this->object->get_request_list($args);
		
		return json_encode($list);
	}
	
	function check_request($request_values)
	{
		$action_name = $this->object->get_action_name();
		$action = $this->object->get_nonce_name();
		
		$prefix = isset($request_values[$action_name . '_prefix']) ? $request_values[$action_name . '_prefix'] : null;
		
		if (isset($request_values[$prefix . $action_name . '_sec']))
		{
			$nonce = $request_values[$prefix . $action_name . '_sec'];
			
			$result = wp_verify_nonce($nonce, $action);
			
			if ($result)
			{
				return true;
			}
		}
		
		return false;
	}
	
	function get_nonce_name()
	{
		$action_name = $this->object->get_action_name();
		$prop_list = $this->object->get_property_list();
		
		$action = $action_name;
		
		foreach ($prop_list as $prop_name)
		{
			$property = $this->object->get_property($prop_name);
			$action .= '_' . strval($property);
		}
		
		return $action;
	}
}

class Mixin_Wordpress_Security_Token_MVC extends Mixin
{
	function check_request($request_values)
	{
		// XXX check URL parameters passed with the MVC module
		//
		return $this->call_parent('check_request', $request_values);
	}
}
	
class C_Wordpress_Security_Token extends C_Security_Token
{
	function define($context=FALSE)
	{
		parent::define($context);

		$this->add_mixin('Mixin_Wordpress_Security_Token');
		$this->add_mixin('Mixin_Wordpress_Security_Token_MVC');
	}
}
