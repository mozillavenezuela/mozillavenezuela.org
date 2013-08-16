<?php

class C_NextGen_Admin_Installer
{
	function __construct()
	{
		$this->settings = C_NextGen_Global_Settings::get_instance();
	}

	function install()
	{
		$registry = C_Component_Registry::get_instance();
		$router = $registry->get_utility('I_Router');
		$theme_url = $router->get_static_url('photocrati-nextgen_admin#jquery-ui/jquery-ui-1.9.1.custom.css');

		$defaults = array(
			'jquery_ui_theme' => 'jquery-ui-nextgen',
			'jquery_ui_theme_version' => 1.8,
			'jquery_ui_theme_url' => $theme_url
		);

		foreach ($defaults as $k=>$v) $this->settings->set_default_value($k, $v);
	}
}
