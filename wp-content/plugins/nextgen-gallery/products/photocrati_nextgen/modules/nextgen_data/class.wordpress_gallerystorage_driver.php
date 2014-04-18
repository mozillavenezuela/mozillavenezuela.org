<?php

class Mixin_WordPress_GalleryStorage_Driver extends Mixin
{
	/**
	 * Returns the named sizes available for images
	 * @global array $_wp_additional_image_sizese
	 * @return array
	 */
	function get_image_sizes()
	{
		global $_wp_additional_image_sizes;
		$_wp_additional_image_sizes[] = 'full';
		return $_wp_additional_image_sizes;
	}


	/**
	 * Gets the upload path for new images in this gallery
	 * This will always be the date-based directory
	 * @param type $gallery
	 * @return type
	 */
	function get_upload_abspath($gallery=FALSE)
	{
		// Gallery is used for this driver, as the upload path is
		// the same, regardless of what gallery is used

		$retval = FALSE;

		$dir = wp_upload_dir(time());
		if ($dir) $retval = $dir['path'];

		return $retval;
	}


	/**
	 * Will always return the same as get_upload_abspath(), as
	 * WordPress storage is not organized by gallery but by date
	 * @param int|object $gallery
	 */
	function get_gallery_abspath($gallery=FALSE)
	{
		return $this->object->get_upload_abspath();
	}


	/**
	 * Gets the absolute path to a particular size of an image
	 * @param int|object $image
	 * @param string $size
	 * @return string
	 */
	function get_image_abspath($image, $size='full')
	{
		return str_replace(
			$this->get_registry()->get_utility('I_Router')->get_base_url(TRUE),
			ABSPATH,
			$this->object->get_image_abspath($image, $size)
		);
	}


	/**
	 * Gets the url of a particular sized image
	 * @param int|object $image
	 * @param type $size
	 * @return string
	 */
	function get_image_url($image=FALSE, $size='full')
	{
		$retval = NULL;
        $image_key = $this->get_registry()->get_utility('I_Displayed_Gallery_Mapper')->get_primary_key_column();

		if ($image && (($image_id = $this->object->_get_image_id($image)))) {
			$parts = wp_get_attachment_image_src($image->$image_key);
			if ($parts) $retval = $parts['url'];
		}

		return $retval;
	}
}

class C_WordPress_GalleryStorage_Driver extends C_GalleryStorage_Driver_Base
{
	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_WordPress_GalleryStorage_Driver');
	}
}
