<?php

class A_Other_Options_Page extends Mixin
{
	function initialize()
	{
		$this->object->add(
			NEXTGEN_OTHER_OPTIONS_SLUG,
			'A_Other_Options_Controller',
			NGGFOLDER
		);
	}
}