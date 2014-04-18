<?php

class A_NextGen_Admin_Default_Pages extends Mixin
{
	function initialize()
	{
		$this->object->add(NGG_FS_ACCESS_SLUG, array(
			'adapter'	=>	'A_Fs_Access_Page',
			'parent'	=>	NGGFOLDER,
			'add_menu'	=>	FALSE
		));
	}
}