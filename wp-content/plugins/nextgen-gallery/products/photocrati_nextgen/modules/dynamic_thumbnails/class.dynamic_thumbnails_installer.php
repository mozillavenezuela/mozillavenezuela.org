<?php

class C_Dynamic_Thumbnails_Installer
{
	function __construct()
	{
		$this->settings = C_NextGen_Settings::get_instance();
	}

	function install()
	{
		$this->settings->set_default_value('dynamic_thumbnail_slug', 'nextgen-image');
	}
}