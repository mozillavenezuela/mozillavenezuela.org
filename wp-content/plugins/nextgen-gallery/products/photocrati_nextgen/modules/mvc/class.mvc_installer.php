<?php

class C_MVC_Installer
{
	function __construct()
	{
		$this->settings = C_NextGen_Settings::get_instance();
	}

	function install()
	{
		$this->settings->delete('mvc_template_dir');
		$this->settings->set_default_value('mvc_template_dirname', '/templates');
		$this->settings->set_default_value('mvc_static_dirname', '/static');
	}
}