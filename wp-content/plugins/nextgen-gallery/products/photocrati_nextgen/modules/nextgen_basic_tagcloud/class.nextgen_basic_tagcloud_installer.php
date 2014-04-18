<?php

class C_NextGen_Basic_Tagcloud_Installer extends C_Gallery_Display_Installer
{
	/**
	 * Installs the display type for NextGEN Basic Tagcloud
	 */
	function install()
	{
		$this->install_display_type(
			NGG_BASIC_TAGCLOUD, array(
				'title'					=>	'NextGEN Basic TagCloud',
				'entity_types'			=>	array('image'),
				'preview_image_relpath'	=>	'photocrati-nextgen_basic_tagcloud#preview.gif',
				'default_source'		=>	'tags',
				'view_order' => NGG_DISPLAY_PRIORITY_BASE + 100
			)

		);
	}
}
