<?php

class A_NextGen_Admin_Default_Pages extends Mixin
{
	function initialize()
	{
		$this->object->add(
			NEXTGEN_FS_ACCESS_SLUG, 'A_Fs_Access_Page', NGGFOLDER, FALSE
		);
	}
}