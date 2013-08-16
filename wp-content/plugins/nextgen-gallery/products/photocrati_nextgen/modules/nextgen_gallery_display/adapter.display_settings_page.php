<?php

class A_Display_Settings_Page extends Mixin
{
	function initialize()
	{
		$this->object->add(
			'ngg_display_settings',
			'A_Display_Settings_Controller',
			NGGFOLDER
		);
	}
}