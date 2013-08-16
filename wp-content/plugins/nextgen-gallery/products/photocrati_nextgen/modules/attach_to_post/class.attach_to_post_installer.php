<?php

class C_Attach_To_Post_Installer
{
	function get_registry()
	{
		return C_Component_Registry::get_instance();
	}

	function __construct()
	{
		$this->settings = C_NextGen_Global_Settings::get_instance();

		$router = $this->get_registry()->get_utility('I_Router');
		$this->defaults = array(
			'attach_to_post_url' => $router->get_url('/nextgen-attach_to_post', FALSE),
			'gallery_preview_url' => $router->get_url('/nextgen-attach_to_post/preview', FALSE),
			'attach_to_post_display_tab_js_url' => $router->get_url('/nextgen-attach_to_post/display_tab_js', FALSE)
		);
	}

	function install()
	{
		foreach ($this->defaults as $key => $val) {
			$this->settings->set_default_value($key, $val);
		}
	}

	function uninstall($hard=FALSE)
	{
		if ($hard) foreach (array_keys($this->defaults) as $key)
			$this->settings->delete($key);
	}
}