<?php

class C_DataMapper_Installer
{
	function __construct()
	{
		$this->settings = C_NextGen_Settings::get_instance();
	}

	function install()
	{
		$this->settings->set_default_value('datamapper_driver', 'custom_post_datamapper');
	}
}