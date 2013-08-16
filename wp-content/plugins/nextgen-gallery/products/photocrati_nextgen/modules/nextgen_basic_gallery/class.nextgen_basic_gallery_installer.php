<?php

class C_NextGen_Basic_Gallery_Installer extends C_Gallery_Display_Installer
{
	function install()
	{
		$this->install_display_type(NEXTGEN_GALLERY_BASIC_THUMBNAILS,
			array(
				'title'					=>	'NextGEN Basic Thumbnails',
				'entity_types'			=>	array('image'),
				'preview_image_relpath'	=>	'photocrati-nextgen_basic_gallery#thumb_preview.jpg',
				'default_source'		=>	'galleries',
				'view_order' => NEXTGEN_DISPLAY_PRIORITY_BASE
			)
		);

		$this->install_display_type(NEXTGEN_GALLERY_BASIC_SLIDESHOW,
			array(
				'title'					=>	'NextGEN Basic Slideshow',
				'entity_types'			=>	array('image'),
				'preview_image_relpath'	=>	'photocrati-nextgen_basic_gallery#slideshow_preview.jpg',
				'default_source'		=>	'galleries',
				'view_order' => NEXTGEN_DISPLAY_PRIORITY_BASE + 10
			)
		);
	}
}
