<?php

class C_MVC_Installer
{
	function __construct()
	{
		$this->settings = C_NextGen_Global_Settings::get_instance();
	}

	function install()
	{
		$this->settings->set_default_value('mvc_module_dir', dirname(__FILE__));
		$this->settings->set_default_value('mvc_template_dir', path_join($this->settings->get('mvc_module_dir'), 'templates'));
		$this->settings->set_default_value('mvc_template_dirname', '/templates');
		$this->settings->set_default_value('mvc_static_dirname', '/static');
	}
}