<?php

class C_Frame_Communication_Installer
{
	function __construct()
	{
		$this->settings = C_NextGen_Settings::get_instance();
	}

	function install()
	{
		$this->settings->set_default_value('frame_communication_option_name', 'X-Frame-Events');
	}
}