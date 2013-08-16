<?php

class C_NextGen_Basic_Album_Installer extends C_Gallery_Display_Installer
{
	function install()
	{
		$this->install_display_type(
			NEXTGEN_GALLERY_NEXTGEN_BASIC_COMPACT_ALBUM, array(
			'title'					=>	'NextGEN Basic Compact Album',
			'entity_types'			=>	array('album', 'gallery'),
			'preview_image_relpath'	=>	'photocrati-nextgen_basic_album#compact_preview.jpg',
			'default_source'		=>	'albums',
			'view_order' => NEXTGEN_DISPLAY_PRIORITY_BASE + 200
		));

		$this->install_display_type(
			NEXTGEN_GALLERY_NEXTGEN_BASIC_EXTENDED_ALBUM, array(
			'title'					=>	'NextGEN Basic Extended Album',
			'entity_types'			=>	array('album', 'gallery'),
			'preview_image_relpath'	=>	'photocrati-nextgen_basic_album#extended_preview.jpg',
			'default_source'		=>	'albums',
			'view_order' => NEXTGEN_DISPLAY_PRIORITY_BASE + 210
		));
	}
}