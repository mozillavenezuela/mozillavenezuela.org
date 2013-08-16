<?php

class A_Watermarking_Ajax_Actions extends Mixin
{
	/**
	 * Gets the new watermark preview url based on the new settings
	 * @return array
	 */
	function get_watermark_preview_url_action()
	{
		$security = $this->get_registry()->get_utility('I_Security_Manager');
		$sec_actor = $security->get_current_actor();
		
		if ($sec_actor->is_allowed('nextgen_edit_settings')) {
			$settings	= C_NextGen_Settings::get_instance();
			$imagegen	= $this->get_registry()->get_utility('I_Dynamic_Thumbnails_Manager');
			$mapper		= $this->get_registry()->get_utility('I_Image_Mapper');
			$image		= $mapper->find_first();
			$storage	= $this->object->get_registry()->get_utility('I_Gallery_Storage');
			$sizeinfo	= array(
				'quality'   => 100,
		          'height'    => 250,
		          'crop'      => FALSE,
		          'watermark' => TRUE
			);
			$size			= $imagegen->get_size_name($sizeinfo);
			$thumbnail_url	= $storage->get_image_url($image, $size);

			// Temporarily update the watermark options. Generate a new image based
			// on these settings
			if (($watermark_options = $this->param('watermark_options'))) {
				$watermark_options['wmFont'] = trim($watermark_options['wmFont']);
				$settings->set($watermark_options);
				$storage->generate_image_size($image, $size);
				$thumbnail_url	= $storage->get_image_url($image, $size);
				$settings->load();
			}

			return array('thumbnail_url' => $thumbnail_url);
		}
		
		return null;
	}
}
