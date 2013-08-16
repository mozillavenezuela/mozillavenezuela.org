<?php

class C_Ajax_Installer
{
	function __construct()
	{
		$this->settings = C_NextGen_Global_Settings::get_instance();
	}

	function get_registry()
	{
		return C_Component_Registry::get_instance();
	}

	function install()
	{
		$slug     = 'photocrati_ajax';
		$router   = $this->get_registry()->get_utility('I_Router');

		$this->settings->set_default_value('ajax_slug', $slug);
		$this->settings->set_default_value('ajax_url', $router->get_url($slug, FALSE));
		$this->settings->set_default_value('ajax_js_url', $router->get_url($slug . '/js', FALSE));
	}

	function uninstall($hard=FALSE)
	{
		if ($hard) foreach (array('ajax_slug', 'ajax_url', 'ajax_js_url') as $key)
			$this->settings->delete($key);
	}
}