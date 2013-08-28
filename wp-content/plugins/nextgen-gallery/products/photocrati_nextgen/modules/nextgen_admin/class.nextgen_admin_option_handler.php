<?php

class C_NextGen_Admin_Option_Handler
{
	function get_router()
	{
		return C_Component_Registry::get_instance()->get_utility('I_Router');
	}


	function get($key, $default=NULL)
	{
		$retval = $default;

		switch ($key) {
			case 'jquery_ui_theme':
				$retval = 'jquery-ui-nextgen';
				break;
			case 'jquery_ui_theme_version':
				$retval = '1.8';
				break;
			case 'jquery_ui_theme_url':
				$retval = $this->get_router()->get_static_url('photocrati-nextgen_admin#jquery-ui/jquery-ui-1.9.1.custom.css');
				break;
		}

		return $retval;
	}
}