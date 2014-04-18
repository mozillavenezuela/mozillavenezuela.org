<?php

class C_Router_Installer
{
	function install()
	{
		$settings = C_NextGen_Settings::get_instance();
		$settings->set_default_value('router_param_separator', '--');
		$settings->set_default_value('router_param_prefix', '');
		$settings->set_default_value('router_param_slug', 'nggallery');
	}
}