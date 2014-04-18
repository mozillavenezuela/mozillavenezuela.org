<?php

class A_Other_Options_Page extends Mixin
{
	function initialize()
	{
		$this->object->add(NGG_OTHER_OPTIONS_SLUG, array(
			'adapter'	=>	'A_Other_Options_Controller',
			'parent'	=>	NGGFOLDER
		));
	}
}