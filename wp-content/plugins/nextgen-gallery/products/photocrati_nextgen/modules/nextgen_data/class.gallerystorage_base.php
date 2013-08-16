<?php

class C_GalleryStorage_Base extends C_Component
{
	/**
	 * Gets the url or path of an image of a particular size
	 * @param string $method
	 * @param array $args
	 */
	function __call($method, $args)
	{
		if (preg_match("/^get_(\w+)_(abspath|url|dimensions|html|size_params)$/", $method, $match)) {
			if (isset($match[1]) && isset($match[2]) && !$this->has_method($method)) {
				$method = 'get_image_'.$match[2];
				$args[] = $match[1]; // array($image, $size)
				return parent::__call($method, $args);
			}
		}
		return parent::__call($method, $args);
	}
}