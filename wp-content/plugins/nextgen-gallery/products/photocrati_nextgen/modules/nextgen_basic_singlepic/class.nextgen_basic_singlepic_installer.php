<?php

class C_NextGen_Basic_SinglePic_Installer extends C_Gallery_Display_Installer
{
	function install()
	{
		$this->install_display_type(
			NEXTGEN_BASIC_SINGLEPIC_MODULE_NAME, array(
			'title'					=>	'NextGEN Basic SinglePic',
			'entity_types'			=>	array('image'),
			'preview_image_relpath'	=>	'photocrati-nextgen_basic_singlepic#preview.gif',
			'default_source'		=>	'galleries',
			'view_order' => NEXTGEN_DISPLAY_PRIORITY_BASE + 60
		));
	}
}
