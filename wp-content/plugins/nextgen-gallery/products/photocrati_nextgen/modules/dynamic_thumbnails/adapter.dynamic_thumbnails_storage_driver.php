<?php

class A_Dynamic_Thumbnails_Storage_Driver extends Mixin
{
	function get_image_abspath($image, $size=FALSE, $check_existance=FALSE)
	{
		$retval = NULL;
		$dynthumbs = $this->object->get_registry()->get_utility('I_Dynamic_Thumbnails_Manager');

		if ($dynthumbs && $dynthumbs->is_size_dynamic($size))
		{
			// If we have the id, get the actual image entity
			if (is_numeric($image)) {
				$image = $this->object->_image_mapper->find($image);
			}

			// Ensure we have the image entity - user could have passed in an
			// incorrect id
			if (is_object($image)) {
				if ($folder_path = $this->object->get_cache_abspath($image->galleryid))
                {
					$params = $dynthumbs->get_params_from_name($size, true);
					$image_filename = $dynthumbs->get_image_name($image, $params);

					$image_path = path_join($folder_path, $image_filename);

					if ($check_existance)
					{
						if (@file_exists($image_path))
						{
							$retval = $image_path;
						}
					}
					else
					{
						$retval = $image_path;
					}
				}
			}
		}
		else  {
			$retval = $this->call_parent('get_image_abspath', $image, $size, $check_existance);
		}

		return $retval;
	}

	function get_image_url($image, $size='full', $check_existance=FALSE)
	{
		$retval = NULL;
		$dynthumbs = $this->object->get_registry()->get_utility('I_Dynamic_Thumbnails_Manager');

		if ($dynthumbs && $dynthumbs->is_size_dynamic($size)) {

			$abspath = $this->object->get_image_abspath($image, $size, true);

		if ($abspath == null) {
				$params = $dynthumbs->get_params_from_name($size, true);
				$retval = $dynthumbs->get_image_url($image, $params);
			}
		}

		if ($retval == null) {
			$retval = $this->call_parent('get_image_url', $image, $size, $check_existance);
		}

		// Try generating the thumbnail
		if ($retval == null) {
			$params = array('watermark' => false, 'reflection' => false, 'crop' => true);
			$result = $this->generate_thumbnail($image, $params);
			if ($result) $retval = $this->call_parent('get_image_url', $image, $size, $check_existance);
		}

		return $retval;
	}

  function get_image_dimensions($image, $size = 'full')
  {
		$retval = $this->call_parent('get_image_dimensions', $image, $size);

		if ($retval == null) {
			$dynthumbs = $this->object->get_registry()->get_utility('I_Dynamic_Thumbnails_Manager');

			if ($dynthumbs && $dynthumbs->is_size_dynamic($size))
			{
				$new_dims = $this->object->calculate_image_size_dimensions($image, $size);

				$retval = array('width' => $new_dims['real_width'], 'height' => $new_dims['real_height']);
			}
		}

		return $retval;
  }

	function get_image_size_params($image, $size, $params = null, $skip_defaults = false)
	{
		$dynthumbs = $this->object->get_registry()->get_utility('I_Dynamic_Thumbnails_Manager');

		if ($dynthumbs && $dynthumbs->is_size_dynamic($size))
		{
			$named_params = $dynthumbs->get_params_from_name($size, true);

			foreach ($named_params as $param_name => $param_value)
			{
				$params[$param_name] = $param_value;
			}
		}

		return $this->call_parent('get_image_size_params', $image, $size, $params, $skip_defaults);
	}
}
