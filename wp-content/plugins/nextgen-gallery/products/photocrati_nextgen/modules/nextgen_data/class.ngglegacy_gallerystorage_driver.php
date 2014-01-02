<?php

class Mixin_NggLegacy_GalleryStorage_Driver extends Mixin
{
	/**
	 * Returns the named sizes available for images
	 * @return array
	 */
	function get_image_sizes()
	{
		return array('full', 'thumbnail');
	}


	function get_upload_abspath($gallery=FALSE)
	{
		// Base upload path
		$retval = C_NextGen_Settings::get_instance()->gallerypath;
    $fs = $this->get_registry()->get_utility('I_Fs');

		// If a gallery has been specified, then we'll
		// append the slug
		if ($gallery) {
			if (!is_object($gallery)) {
				$gallery = $this->object->_get_gallery_id($gallery);
				$gallery = $this->object->_gallery_mapper->find($gallery);
			}
			
			if ($gallery) {
				$path = $gallery->path;
				$base = basename($path);
				$slug = $gallery->slug;
				
				if ($base == null) {
					$base = $slug;
				}
				
				$retval = $fs->join_paths($retval, $base);
			}
		}

		// We need to make this an absolute path
		if (strpos($retval, $fs->get_document_root()) === FALSE)
            $retval = $fs->join_paths($fs->get_document_root(), $retval);

		return $retval;
	}


	/**
	 * Get the gallery path persisted in the database for the gallery
	 * @param int|stdClass|C_NextGen_Gallery $gallery
	 */
	function get_gallery_abspath($gallery)
	{
		$retval = NULL;
        $fs = $this->get_registry()->get_utility('I_Fs');

		// Get the gallery entity from the database
		if ($gallery) {
			if (is_numeric($gallery)) {
				$gallery = $this->object->_gallery_mapper->find($gallery);
			}
		}

		// If a path was stored in the entity, then use that
		if ($gallery && isset($gallery->path)) {
			$retval = $fs->join_paths($fs->get_document_root(), $gallery->path);
		}
        elseif ($gallery) {
            // fallback to the upload abspath
            $storage = $this->object->get_registry()->get_utility('I_Gallery_Storage');
            $retval = $storage->get_upload_abspath($gallery);
        }

		return $retval;
	}


	/**
	 * Gets the absolute path where the image is stored
	 * Can optionally return the path for a particular sized image
	 */
	function get_image_abspath($image, $size='full', $check_existance=FALSE)
	{
		$retval = NULL;
        $fs = $this->get_registry()->get_utility('I_Fs');

        // Ensure that we have a size
		if (!$size) {
			$size = 'full';
		}

		// If we have the id, get the actual image entity
		if (is_numeric($image)) {
			$image = $this->object->_image_mapper->find($image);
		}

		// Ensure we have the image entity - user could have passed in an
		// incorrect id
		if (is_object($image)) {
			if (($gallery_path = $this->object->get_gallery_abspath($image->galleryid))) {
				$folder = $prefix = $size;
				switch ($size) {

					# Images are stored in the associated gallery folder
					case 'full':
					case 'original':
					case 'image':
						$retval = $fs->join_paths($gallery_path, $image->filename);
						break;

					case 'thumbnails':
					case 'thumbnail':
					case 'thumb':
					case 'thumbs':
						$size = 'thumbnail';
						$folder = 'thumbs';
						$prefix = 'thumbs';
						// deliberately no break here

					// We assume any other size of image is stored in the a
					//subdirectory of the same name within the gallery folder
					// gallery folder, but with the size appended to the filename
					default:
						$image_path = $fs->join_paths($gallery_path, $folder);

						// NGG 2.0 stores relative filenames in the meta data of
						// an image. It does this because it uses filenames
						// that follow conventional WordPress naming scheme.
						if (isset($image->meta_data) && isset($image->meta_data[$size]) && isset($image->meta_data[$size]['filename'])) {
							$image_path = $fs->join_paths($image_path, $image->meta_data[$size]['filename']);
						}

						// NGG Legacy does not store relative filenames in the
						// image entity for sizes other than the original.
						// Although the naming scheme for filenames differs from
						// WordPress conventions, NGG legacy does follow it's
						// own naming schema consistently so we can guess the path
						else {
							$image_path = $fs->join_paths($image_path, "{$prefix}_{$image->filename}");
						}

						// Should we check whether the image actually exists?
						if ($check_existance && @file_exists($image_path)) {
							$retval = $image_path;
						}
						elseif (!$check_existance) $retval = $image_path;
						break;
				}
			}
		}

		return $retval;
	}


	/**
	 * Gets the url of a particular-sized image
	 * @param int|object $image
	 * @param string $size
	 * @returns array
	 */
	function get_image_url($image, $size='full', $check_existance=FALSE)
	{
		$retval  = NULL;
		$fs		 = $this->get_registry()->get_utility('I_Fs');
		$router	 = $this->get_registry()->get_utility('I_Router');
		$abspath = $this->object->get_image_abspath($image, $size, $check_existance);
		if ($abspath) {
			$doc_root = $fs->get_document_root();
			
			if ($doc_root != null) {
				$doc_root = trailingslashit($doc_root);
			}
			
			$request_uri = str_replace(
				$doc_root,
				'',
				$abspath
			);
			
			if ($request_uri != null && $request_uri[0] != '/') {
				$request_uri = '/' . $request_uri;
			}
			
			$retval = $router->remove_url_segment('/index.php', $router->get_url($request_uri, FALSE, TRUE));
		}

		return $retval;
	}

	/**
	 * Uploads an image for a particular gallerys
	 * @param int|stdClass|C_NextGEN_Gallery $gallery
	 * @param type $filename, specifies the name of the file
	 * @param type $data if specified, expects base64 encoded string of data
	 * @return C_Image
	 */
	function upload_image($gallery, $filename=FALSE, $data=FALSE)
	{
		$retval = NULL;

		// Ensure that we have the data present that we require
		if ((isset($_FILES['file']) && $_FILES['file']['error'] == 0)) {

			//		$_FILES = Array(
			//		 [file]	=>	Array (
			//            [name] => Canada_landscape4.jpg
			//            [type] => image/jpeg
			//            [tmp_name] => /private/var/tmp/php6KO7Dc
			//            [error] => 0
			//            [size] => 64975
			//         )
			//
			$file = $_FILES['file'];

            if ($this->object->is_zip()) {
                $retval = $this->object->upload_zip($gallery);
            }
            else {
                $retval = $this->object->upload_base64_image(
                    $gallery,
                    file_get_contents($file['tmp_name']),
                    $filename ? $filename : (isset($file['name']) ? $file['name'] : FALSE)
                );
            }
		}
		elseif ($data) {
			$retval = $this->object->upload_base64_image(
				$filename,
				$data
			);
		}
		else throw new E_UploadException();

		return $retval;
	}

	function get_image_size_params($image, $size, $params = null, $skip_defaults = false)
	{
		// Get the image entity
		if (is_numeric($image)) {
			$image = $this->object->_image_mapper->find($image);
		}

		// Ensure we have a valid image
		if ($image)
		{
			$settings = C_NextGen_Settings::get_instance();

			if (!$skip_defaults)
			{
				// Get default settings
				if ($size == 'full') {
					if (!isset($params['quality'])) {
						$params['quality'] = $settings->imgQuality;
					}
				}
				else {
					if (!isset($params['crop'])) {
						$params['crop'] = $settings->thumbfix;
					}

					if (!isset($params['quality'])) {
						$params['quality'] = $settings->thumbquality;
					}
				}

				// Not sure why this was here... commenting out for now, always require watermark parameters to be explicit
#				if (!isset($params['watermark'])) {
#					$params['watermark'] = $settings->wmType;
#				}
			}

			// width and height when omitted make generate_image_clone create a clone with original size, so try find defaults regardless of $skip_defaults
			if (!isset($params['width']) || !isset($params['height'])) {
				// First test if this is a "known" image size, i.e. if we store these sizes somewhere when users re-generate these sizes from the UI...this is required to be compatible with legacy
				// try the 2 default built-in sizes, first thumbnail...
				if ($size == 'thumbnail') {
					if (!isset($params['width'])) {
						$params['width'] = $settings->thumbwidth;
					}

					if (!isset($params['height'])) {
						$params['height'] = $settings->thumbheight;
					}
				}
				// ...and then full, which is the size specified in the global resize options
				else if ($size == 'full') {
					if (!isset($params['width'])) {
						if ($settings->imgAutoResize) {
							$params['width'] = $settings->imgWidth;
						}
					}

					if (!isset($params['height'])) {
						if ($settings->imgAutoResize) {
							$params['height'] = $settings->imgHeight;
						}
					}
				}
				// Only re-use old sizes as last resort
				else if (isset($image->meta_data) && isset($image->meta_data[$size])) {
					$dimensions = $image->meta_data[$size];

					if (!isset($params['width'])) {
						$params['width'] = $dimensions['width'];
					}

					if (!isset($params['height'])) {
						$params['height'] = $dimensions['height'];
					}
				}
			}

			if (!isset($params['crop_frame'])) {
				$crop_frame_size_name = 'thumbnail';

				if (isset($image->meta_data[$size]['crop_frame'])) {
					$crop_frame_size_name = $size;
				}

				if (isset($image->meta_data[$crop_frame_size_name]['crop_frame'])) {
					$params['crop_frame'] = $image->meta_data[$crop_frame_size_name]['crop_frame'];

					if (!isset($params['crop_frame']['final_width'])) {
						$params['crop_frame']['final_width'] = $image->meta_data[$crop_frame_size_name]['width'];
					}

					if (!isset($params['crop_frame']['final_height'])) {
						$params['crop_frame']['final_height'] = $image->meta_data[$crop_frame_size_name]['height'];
					}
				}
			}
			else {
				if (!isset($params['crop_frame']['final_width'])) {
					$params['crop_frame']['final_width'] = $params['width'];
				}

				if (!isset($params['crop_frame']['final_height'])) {
					$params['crop_frame']['final_height'] = $params['height'];
				}
			}
		}

		return $params;
	}

	/**
	 * Returns an array of dimensional properties (width, height, real_width, real_height) of a resulting clone image if and when generated
	 * @param string $image_path
	 * @param string $clone_path
	 * @param array $params
	 * @return array
	 */
	function calculate_image_size_dimensions($image, $size, $params = null, $skip_defaults = false)
	{
		$retval = FALSE;

		// Get the image entity
		if (is_numeric($image)) {
			$image = $this->object->_image_mapper->find($image);
		}

		// Ensure we have a valid image
		if ($image)
		{
			$params = $this->object->get_image_size_params($image, $size, $params, $skip_defaults);

			// Get the image filename
			$image_path = $this->object->get_original_abspath($image, 'original');
			$clone_path = $this->object->get_image_abspath($image, $size);

			$retval = $this->object->calculate_image_clone_dimensions($image_path, $clone_path, $params);
		}

		return $retval;
	}

	/**
	 * Generates a specific size for an image
	 * @param int|stdClass|C_Image $image
	 * @return bool|object
	 */
	function generate_image_size($image, $size, $params = null, $skip_defaults = false)
	{
		$retval = FALSE;

		// Get the image entity
		if (is_numeric($image)) {
			$image = $this->object->_image_mapper->find($image);
		}

		// Ensure we have a valid image
		if ($image)
		{
			$params = $this->object->get_image_size_params($image, $size, $params, $skip_defaults);
			$settings = C_NextGen_Settings::get_instance();

			// Get the image filename
			$filename = $this->object->get_original_abspath($image, 'original');
			$thumbnail = null;

			if ($size == 'full' && $settings->imgBackup == 1) {
				// XXX change this? 'full' should be the resized path and 'original' the _backup path
				$backup_path = $this->object->get_backup_abspath($image);

				if (!@file_exists($backup_path))
				{
					@copy($filename, $backup_path);
				}
			}

			// Generate the thumbnail using WordPress
			$existing_image_abpath = $this->object->get_image_abspath($image, $size);
			$existing_image_dir = dirname($existing_image_abpath);

			// removing the old thumbnail is actually not needed as generate_image_clone() will replace it, leaving commented in as reminder in case there are issues in the future
			if (@file_exists($existing_image_abpath)) {
				//unlink($existing_image_abpath);
			}

			wp_mkdir_p($existing_image_dir);

			$clone_path = $existing_image_abpath;
			$thumbnail = $this->object->generate_image_clone($filename, $clone_path, $params);

			// We successfully generated the thumbnail
			if ($thumbnail != null)
			{
				$clone_path = $thumbnail->fileName;

				if (function_exists('getimagesize'))
				{
					$dimensions = getimagesize($clone_path);
				}
				else
				{
					$dimensions = array($params['width'], $params['height']);
				}

				if (!isset($image->meta_data))
				{
					$image->meta_data = array();
				}

				$size_meta = array(
					'width'		=> $dimensions[0],
					'height'	=> $dimensions[1],
					'filename'	=> basename($clone_path),
					'generated'	=> microtime()
				);

				if (isset($params['crop_frame'])) {
					$size_meta['crop_frame'] = $params['crop_frame'];
				}

				$image->meta_data[$size] = $size_meta;

				if ($size == 'full')
				{
					$image->meta_data['width'] = $size_meta['width'];
					$image->meta_data['height'] = $size_meta['height'];
				}

				$retval = $this->object->_image_mapper->save($image);

				if ($retval == 0) {
					$retval = false;
				}

				if ($retval) {
					$retval = $thumbnail;
				}
			}
			else {
				// Something went wrong. Thumbnail generation failed!
			}
		}

		return $retval;
	}

	/**
	 * Generates a thumbnail for an image
	 * @param int|stdClass|C_Image $image
	 * @return bool
	 */
	function generate_thumbnail($image, $params = null, $skip_defaults = false)
	{
		$sized_image = $this->object->generate_image_size($image, 'thumbnail', $params, $skip_defaults);
		$retval = false;
		
		if ($sized_image != null)
		{
			$retval = true;
		
			$sized_image->destruct();
		}

		return $retval;
	}

	/**
	 * Outputs/renders an image
	 * @param int|stdClass|C_NextGen_Gallery_Image $image
	 * @return bool
	 */
	function render_image($image, $size=FALSE)
	{
		$format_list = $this->object->get_image_format_list();
		$abspath = $this->get_image_abspath($image, $size, true);

		if ($abspath == null)
		{
			$thumbnail = $this->object->generate_image_size($image, $size);

			if ($thumbnail != null)
			{
				$abspath = $thumbnail->fileName;

				$thumbnail->destruct();
			}
		}

		if ($abspath != null)
		{
			$data = @getimagesize($abspath);
			$format = 'jpg';

			if ($data != null && is_array($data) && isset($format_list[$data[2]]))
			{
				$format = $format_list[$data[2]];
			}

			// Clear output
			while (ob_get_level() > 0)
			{
				ob_end_clean();
			}

			$format = strtolower($format);

			// output image and headers
			header('Content-type: image/' . $format);
			readfile($abspath);

			return true;
		}

		return false;
	}

	function delete_image($image, $size=FALSE)
	{
		$retval = FALSE;

		// Ensure that we have the image entity
		if (is_numeric($image))
            $image = $this->object->_image_mapper->find($image);

		if ($image)
        {
			// Delete only a particular image size
			if ($size)
            {
				$abspath = $this->object->get_image_abspath($image, $size);
				if ($abspath && @file_exists($abspath))
                    unlink($abspath);
				if (isset($image->meta_data) && isset($image->meta_data[$size]))
                {
					unset($image->meta_data[$size]);
					$this->object->_image_mapper->save($image);
				}
			}
			// Delete all sizes of the image
			else {
				// Get the paths to fullsize and thumbnail files
				$abspaths = array(
                    $this->object->get_full_abspath($image),
                    $this->object->get_thumb_abspath($image)
                );

				if (isset($image->meta_data))
                {
                    foreach (array_keys($image->meta_data) as $size) {
                        $abspaths[] = $this->object->get_image_abspath($image, $size);
                    }
                }

				// Delete each image
				foreach ($abspaths as $abspath) {
					if ($abspath && @file_exists($abspath))
                    {
                        unlink($abspath);
                    }
                }

				// Delete the entity
				$this->object->_image_mapper->destroy($image);
			}
			$retval = TRUE;
		}

		return $retval;
	}

    /**
     * Copies (or moves) images into another gallery
     *
     * @param array $images
     * @param int|object $gallery
     * @param boolean $db optionally only copy the image files
     * @param boolean $move move the image instead of copying
     * @return mixed NULL on failure, array|image-ids on success
     */
    function copy_images($images, $gallery, $db = TRUE, $move = FALSE)
    {
        // return values
        $message        = '';
        $new_image_pids = array();

        $settings = C_NextGen_Settings::get_instance();
        $fs = $this->get_registry()->get_utility('I_Fs');

        // move_images() is a wrapper to this function so we implement both features here
        $func = $move ? 'rename' : 'copy';

        // ngg-legacy allows for arrays of just the ID
        if (!is_array($images))
        {
            $images = array($images);
        }

        // Ensure we have a valid gallery
        $gallery_id = $this->object->_get_gallery_id($gallery);
        if (!$gallery_id)
        {
            return;
        }

        $image_key = $this->object->_image_mapper->get_primary_key_column();

        // Check for folder permission
        if (!is_dir($gallery->path) && !wp_mkdir_p($gallery->path))
        {
            $message .= sprintf(__('Unable to create directory %s.', 'nggallery'), esc_html(WINABSPATH . $gallery->path));
            return;
        }
        if (!is_writable(WINABSPATH . $gallery->path))
        {
            $message .= sprintf(__('Unable to write to directory %s. Is this directory writable by the server?', 'nggallery'), esc_html(WINABSPATH . $gallery->path));
            return;
        }

        foreach ($images as $image) {
			if ($this->object->is_current_user_over_quota()) {
				$message = sprintf(__('Sorry, you have used your space allocation. Please delete some files to upload more files.', 'nggallery'));
				throw new E_NoSpaceAvailableException($message);
			}

            // Copy the db entry
            if (is_numeric($image))
            {
                $image = $this->object->_image_mapper->find($image);
            }
            $old_pid = $image->$image_key;

            if ($db)
            {
                $new_image = clone $image;
                unset($new_image->$image_key);
                $new_image->galleryid = $gallery_id;
                $new_pid = $this->object->_image_mapper->save($new_image);
                $new_image = $this->object->_image_mapper->find($new_image);
            } else {
                $new_pid = $old_pid;
            }

            if (!$new_pid) {
                $message .= sprintf(__('Failed to copy database row for picture %s', 'nggallery'), $old_pid) . '<br />';
                continue;
            }

            $new_image_pids[] = $new_pid;

            // Copy each image size
            foreach ($this->object->get_image_sizes() as $size) {

                $orig_path = $this->object->get_image_abspath($image, $size, TRUE);
                if (!$orig_path)
                {
                    $message .= sprintf(__('Failed to get image path for %s', 'nggallery'), esc_html($image->filename)) . '<br/>';
                    continue;
                }

                $new_path = basename($orig_path);

                $prefix       = '';
                $prefix_count = 0;
                while (@file_exists($gallery->path . DIRECTORY_SEPARATOR . $new_path))
                {
                    $prefix = 'copy_' . ($prefix_count++) . '_';
                    $new_path = $prefix . $new_path;
                }
                $new_path = $fs->join_paths($gallery->path, $new_path);

                // Copy files
                if (!@$func($orig_path, $new_path))
                {
                    $message .= sprintf(__('Failed to copy image %1$s to %2$s', 'nggallery'), esc_html($orig_path), esc_html($new_path)) . '<br/>';
                    continue;
                }
                else {
                    $message .= sprintf(__('Copied image %1$s to %2$s', 'nggallery'), esc_html($orig_path), esc_html($new_path)) . '<br/>';
                }

                // Copy backup file, if possible
                @$func($orig_path . '_backup', $new_path . '_backup');

                if ($prefix != '')
                {
                    $message .= sprintf(__('Image %1$s (%2$s) copied as image %3$s (%4$s) &raquo; The file already existed in the destination gallery.', 'nggallery'), $old_pid, esc_html($orig_path), $new_pid, esc_html($new_path)) . '<br />';
                }
                else
                {
                    $message .= sprintf(__('Image %1$s (%2$s) copied as image %3$s (%4$s)', 'nggallery'), $old_pid, esc_html($orig_path), $new_pid, esc_html($new_path)) . '<br />';
                }

                // Copy tags
                if ($db)
                {
                    $tags = wp_get_object_terms($old_pid, 'ngg_tag', 'fields=ids');
                    $tags = array_map('intval', $tags);
                    wp_set_object_terms($new_pid, $tags, 'ngg_tag', true);
                }
            }
        }

        $message .= '<hr />' . sprintf(__('Copied %1$s picture(s) to gallery %2$s .', 'nggallery'), count($new_image_pids), $gallery->title);

        return $new_image_pids;
    }

    /**
     * Recover image from backup copy and reprocess it
     *
     * @param int|stdClass|C_Image $image
     * @return string result code
     */
    function recover_image($image) {

        if (is_numeric($image))
        {
            $image = $this->object->_image_mapper->find($image);
        }

        if (isset($image->meta_data))
        {
            $orig_metadata = $image->meta_data;
        }

        $path = $this->object->get_registry()->get_utility('I_Gallery_Storage')->get_image_abspath($image);

        if (!is_object($image))
        {
            return __("Could not find image", 'nggallery');
        }

        if (!is_writable($path) && !is_writable(dirname($path)))
        {
            return ' <strong>' . esc_html($image->filename) . __(' is not writeable', 'nggallery') . '</strong>';
        }

        if (!@file_exists($path . '_backup'))
        {
            return ' <strong>' . __('Backup file does not exist', 'nggallery') . '</strong>';
        }

        if (!@copy($path . '_backup', $path))
        {
            return ' <strong>' . __("Could not restore original image", 'nggallery') . '</strong>';
        }

        if (isset($orig_metadata))
        {
            $NextGen_Metadata = new C_NextGen_Metadata($image);
            $new_metadata = $NextGen_Metadata->get_common_meta();
            $image->meta_data = array_merge((array)$orig_metadata, (array)$new_metadata);
            $this->object->_image_mapper->save($image);
        }

        return '1';
    }
}

class C_NggLegacy_GalleryStorage_Driver extends C_GalleryStorage_Driver_Base
{
	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_NggLegacy_GalleryStorage_Driver');
	}
}
