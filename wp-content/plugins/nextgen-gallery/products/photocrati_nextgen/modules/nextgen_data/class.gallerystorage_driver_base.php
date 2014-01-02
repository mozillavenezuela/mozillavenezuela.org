<?php

class E_UploadException extends E_NggErrorException
{
	function __construct($message='', $code=NULL, $previous=NULL)
	{
		if (!$message) $message = "There was a problem uploading the file.";
		parent::__construct($message, $code, $previous);
	}
}

class E_InsufficientWriteAccessException extends E_NggErrorException
{
	function __construct($message=FALSE, $filename=NULL, $code=NULL, $previous=NULL)
	{
		if (!$message) $message = "Could not write to file. Please check filesystem permissions.";
		if ($filename) $message .= " Filename: {$filename}";
		if (PHP_VERSION_ID >= 50300)
			parent::__construct($message, $code, $previous);
		else
			parent::__construct($message, $code);
	}
}

class E_NoSpaceAvailableException extends E_NggErrorException
{
	function __construct($message='', $code=NULL, $previous=NULL)
	{
		if (!$message) $message = "You have exceeded your storage capacity. Please remove some files and try again.";
		parent::__construct($message, $code, $previous);
	}
}
 
class E_No_Image_Library_Exception extends E_NggErrorException
{
	function __construct($message='', $code=NULL, $previous=NULL)
	{
		if (!$message) $message = "The site does not support the GD Image library. Please ask your hosting provider to enable it.";
		parent::__construct($message, $code, $previous);
	}
}


class Mixin_GalleryStorage_Driver_Base extends Mixin
{
	/**
	 * Set correct file permissions (taken from wp core). Should be called
	 * after writing any file
	 *
	 * @class nggAdmin
	 * @param string $filename
	 * @return bool $result
	 */
	function _chmod($filename = '')
	{
		$stat = @ stat( dirname($filename) );
		$perms = $stat['mode'] & 0000666; // Remove execute bits for files
		if ( @chmod($filename, $perms) )
			return TRUE;

		return FALSE;
	}

    /**
     * Gets the id of a gallery, regardless of whether an integer
     * or object was passed as an argument
     * @param mixed $gallery_obj_or_id
     */
    function _get_gallery_id($gallery_obj_or_id)
    {
        $retval = NULL;
        $gallery_key = $this->object->_gallery_mapper->get_primary_key_column();
        if (is_object($gallery_obj_or_id)) {
            if (isset($gallery_obj_or_id->$gallery_key)) {
                $retval = $gallery_obj_or_id->$gallery_key;
            }
        }
        elseif(is_numeric($gallery_obj_or_id)) {
            $retval = $gallery_obj_or_id;
        }

        return $retval;
    }

    /**
     * Gets the id of an image, regardless of whether an integer
     * or object was passed as an argument
     * @param type $image_obj_or_id
     */
    function _get_image_id($image_obj_or_id)
    {
        $retval = NULL;

        $image_key = $this->object->_image_mapper->get_primary_key_column();
        if (is_object($image_obj_or_id)) {
            if (isset($image_obj_or_id->$image_key)) {
                $retval = $image_obj_or_id->$image_key;
            }
        }
        elseif (is_numeric($image_obj_or_id)) {
            $retval = $image_obj_or_id;
        }

        return $retval;
    }


    function delete_directory($abspath)
    {
        $retval = FALSE;

        if (@file_exists($abspath)) {
            $files = scandir($abspath);
            array_shift($files);
            array_shift($files);
            foreach ($files as $file) {
                $file_abspath = path_join($abspath, $file);
                if (is_dir($file_abspath)) $this->object->delete_directory($file_abspath);
                else unlink($file_abspath);
            }
            rmdir($abspath);
            $retval = @file_exists($abspath);
        }

        return $retval;
    }

    /**
     * Backs up an image file
     * @param int|object $image
     */
    function backup_image($image)
    {
        $retval = FALSE;

        if (($image_path = $this->object->get_image_abspath($image))) {
            $retval = copy($image_path, $this->object->get_backup_abspath($image));
        }

        return $retval;
    }

    /**
     * Copies images into another gallery
     * @param array $images
     * @param int|object $gallery
     * @param boolean $db optionally only copy the image files
     * @param boolean $move move the image instead of copying
     */
    function copy_images($images, $gallery, $db=TRUE, $move=FALSE)
    {
        $retval = FALSE;

        // Ensure we have a valid gallery
        if (($gallery = $this->object->_get_gallery_id($gallery))) {
            $gallery_path = $this->object->get_gallery_abspath($gallery);
            $image_key = $this->object->_image_mapper->get_primary_key_column();
            $retval = TRUE;

            // Iterate through each image to copy...
            foreach ($images as $image) {

                // Copy each image size
                foreach ($this->object->get_image_sizes() as $size) {
                    $image_path = $this->object->get_image_abspath($image, $size);
                    $dst = path_join($gallery_path, basename($image_path));
                    $success = $move ? move($image_path, $dst) : copy($image_path, $dst);
                    if (!$success) $retval = FALSE;
                }

                // Copy the db entry
                if ($db) {
                    if (is_numeric($image)) $this->object->_image_mapper($image);
                    unset($image->$image_key);
                    $image->galleryid = $gallery;
                }
            }
        }

        return $retval;
    }

    /**
     * Empties the gallery cache directory of content
     */
    function flush_cache($gallery)
    {
        $cache = $this->object->get_registry()->get_utility('I_Cache');
        $cache->flush_directory($this->object->get_cache_abspath($gallery));
    }

    /**
     * Gets the absolute path of the backup of an original image
     * @param string $image
     */
    function get_backup_abspath($image)
    {
        $retval = NULL;

        if (($image_path = $this->object->get_image_abspath($image))) {
            $retval = $image_path.'_backup';
        }

        return $retval;
    }

    /**
     * Returns the absolute path to the cache directory of a gallery.
     *
     * Without the gallery parameter the legacy (pre 2.0) shared directory is returned.
     *
     * @param int|stdClass|C_Gallery $gallery (optional)
     * @return string Absolute path to cache directory
     */
    function get_cache_abspath($gallery = FALSE)
    {
        $retval = NULL;

        if (FALSE == $gallery)
        {
            $gallerypath = C_NextGen_Settings::get_instance()->gallerypath;
            $retval = path_join(WINABSPATH, $gallerypath);
            $retval = path_join($retval, 'cache');
        }
        else {
            if (is_numeric($gallery))
            {
                $gallery = $this->object->_gallery_mapper->find($gallery);
            }
            $retval = path_join($this->object->get_gallery_abspath($gallery), 'dynamic');
        }

        return $retval;
    }

    /**
	 * Gets the absolute path where the full-sized image is stored
	 * @param int|object $image
	 */
	function get_full_abspath($image)
	{
		return $this->object->get_image_abspath($image, 'full');
	}

    /**
     * Alias to get_image_dimensions()
     * @param int|object $image
     * @return array
     */
    function get_full_dimensions($image)
    {
        return $this->object->get_image_dimensions($image, 'full');
    }

    /**
     * Alias to get_image_html()
     * @param int|object $image
     * @return string
     */
    function get_full_html($image)
    {
        return $this->object->get_image_html($image, 'full');
    }

    /**
     * Alias for get_original_url()
     *
     * @param int|stdClass|C_Image $image
     * @return string
     */
    function get_full_url($image, $check_existance=FALSE)
    {
        return $this->object->get_image_url($image, 'full', $check_existance);
    }

    /**
     * Gets the dimensions for a particular-sized image
     *
     * @param int|object $image
     * @param string $size
     * @return array
     */
    function get_image_dimensions($image, $size='full')
    {
			$retval = NULL;

        // If an image id was provided, get the entity
        if (is_numeric($image)) $image = $this->object->_image_mapper->find($image);

        // Ensure we have a valid image
        if ($image) {

            // Adjust size parameter
            switch ($size) {
                case 'original':
                    $size = 'full';
                    break;
                case 'thumbnails':
                case 'thumbnail':
                case 'thumb':
                case 'thumbs':
                    $size = 'thumbnail';
                    break;
            }

            // Image dimensions are stored in the $image->meta_data
            // property for all implementations
            if (isset($image->meta_data) && isset($image->meta_data[$size])) {
                $retval = $image->meta_data[$size];
            }

				// Didn't exist for meta data. We'll have to compute
				// dimensions in the meta_data after computing? This is most likely
				// due to a dynamic image size being calculated for the first time
				else {
				
					$abspath = $this->object->get_image_abspath($image, $size);
				
					if (@file_exists($abspath))
					{
						$dims = getimagesize($abspath);
						
						if ($dims) {
							$retval['width']	= $dims[0];
							$retval['height']	= $dims[1];
						}
					}
				}
      }

    	return $retval;
    }

    /**
     * Gets the HTML for an image
     * @param int|object $image
     * @param string $size
     * @return string
     */
    function get_image_html($image, $size='full', $attributes=array())
    {
        $retval = "";

        if (is_numeric($image)) $image = $this->object->_image_mapper->find($image);

        if ($image) {

			// Set alt text if not already specified
			if (!isset($attributes['alttext'])) {
				$attributes['alt'] = esc_attr($image->alttext);
			}

			// Set the title if not already set
			if (!isset($attributes['title'])) {
				$attributes['title'] = esc_attr($image->alttext);
			}

			// Set the dimensions if not set already
			if (!isset($attributes['width']) OR !isset($attributes['height'])) {
				$dimensions = $this->object->get_image_dimensions($image, $size);
				if (!isset($attributes['width'])) {
					$attributes['width'] = $dimensions['width'];
				}
				if (!isset($attributes['height'])) {
					$attributes['height'] = $dimensions['height'];
				}
			}

			// Set the url if not already specified
			if (!isset($attributes['src'])) {
				$attributes['src'] = $this->object->get_image_url($image, $size);
			}

			// Format attributes
			$attribs = array();
			foreach ($attributes as $attrib => $value) $attribs[] = "{$attrib}=\"{$value}\"";
			$attribs = implode(" ", $attribs);

			// Return HTML string
			$retval = "<img {$attribs} />";
        }

        return $retval;
    }

    /**
     * An alias for get_full_abspath()
     * @param int|object $image
     */
    function get_original_abspath($image, $check_existance=FALSE)
    {
        return $this->object->get_image_abspath($image, 'full', $check_existance);
    }

    /**
     * Alias to get_image_dimensions()
     * @param int|object $image
     * @return array
     */
    function get_original_dimensions($image)
    {
        return $this->object->get_image_dimensions($image, 'full');
    }

    /**
     * Alias to get_image_html()
     * @param int|object $image
     * @return string
     */
    function get_original_html($image)
    {
        return $this->object->get_image_html($image, 'full');
    }

    /**
     * Gets the url to the original-sized image
     * @param int|stdClass|C_Image $image
     * @return string
     */
    function get_original_url($image, $check_existance=FALSE)
    {
        return $this->object->get_image_url($image, 'full', $check_existance);
    }

	/**
	 * Gets the upload path, optionally for a particular gallery
	 * @param int|C_Gallery|stdClass $gallery
	 */
	function get_upload_relpath($gallery=FALSE)
	{
		return str_replace(ABSPATH, '', $this->object->get_upload_abspath($gallery));
	}

	/**
	 * Moves images from to another gallery
	 * @param array $images
	 * @param int|object $gallery
	 * @param boolean $db optionally only move the image files, not the db entries
	 * @return boolean
	 */
	function move_images($images, $gallery, $db=TRUE)
	{
		return $this->object->copy_images($images, $gallery, $db, TRUE);
	}


    function is_zip()
    {
        $retval = FALSE;
        
        if ((isset($_FILES['file']) && $_FILES['file']['error'] == 0)) {
            $file_info = $_FILES['file'];
            
            if (isset($file_info['type'])) {
            	$type = $file_info['type'];
            	$type_parts = explode('/', $type);
            	
            	if (strtolower($type_parts[0]) == 'application') {
            		$spec = $type_parts[1];
            		$spec_parts = explode('-', $spec);
            		$spec_parts = array_map('strtolower', $spec_parts);
            		
            		if (in_array($spec, array('zip', 'octet-stream')) || in_array('zip', $spec_parts)) {
            			$retval = true;
            		}
            	}
            }
        }

        return $retval;
    }

    function upload_zip($gallery_id)
    {
        $memory_limit = intval(ini_get('memory_limit'));
        if ($memory_limit < 256) @ini_set('memory_limit', '256M');

        $retval = FALSE;

        if ($this->object->is_zip()) {
            $zipfile    = $_FILES['file']['tmp_name'];
            $dest_path  = path_join(get_temp_dir(), 'unpacked-'.basename($zipfile));
            $fs         = $this->get_registry()->get_utility('I_Fs');

            // Ensure that we truly have the gallery id
            $gallery_id = $this->_get_gallery_id($gallery_id);

            // Uses the WordPress ZIP abstraction API
            wp_mkdir_p($dest_path);
            include_once($fs->join_paths(ABSPATH, 'wp-admin', 'includes', 'file.php'));
            WP_Filesystem();
            if ((unzip_file($zipfile, $dest_path) === TRUE)) {
                $retval = $this->object->import_gallery_from_fs($dest_path, $gallery_id);
            }
            $this->object->delete_directory($dest_path);
        }

        @ini_set('memory_limit', $memory_limit.'M');

        return $retval;
    }

	function is_current_user_over_quota()
	{
		$retval = FALSE;
		$settings = C_NextGen_Settings::get_instance();

		if ((is_multisite()) && $settings->get('wpmuQuotaCheck')) {
			require_once(ABSPATH . 'wp-admin/includes/ms.php');
			$retval = upload_is_user_over_quota(FALSE);
		}

		return $retval;
	}


	/**
	 * Uploads base64 file to a gallery
	 * @param int|stdClass|C_Gallery $gallery
	 * @param $data base64-encoded string of data representing the image
	 * @param type $filename specifies the name of the file
	 * @return C_Image
	 */
	function upload_base64_image($gallery, $data, $filename=FALSE, $image_id=FALSE)
	{
        $settings = C_NextGen_Settings::get_instance();
        $memory_limit = intval(ini_get('memory_limit'));
        if ($memory_limit < 256) @ini_set('memory_limit', '256M');

		$retval		= NULL;
		if (($gallery_id = $this->object->_get_gallery_id($gallery))) {

			if ($this->object->is_current_user_over_quota()) {
				$message = sprintf(__('Sorry, you have used your space allocation. Please delete some files to upload more files.', 'nggallery'));
				throw new E_NoSpaceAvailableException($message);
			}

			// Get path information. The use of get_upload_abspath() might
			// not be the best for some drivers. For example, if using the
			// WordPress Media Library for uploading, then the wp_upload_bits()
			// function should perhaps be used
			$upload_dir = $this->object->get_upload_abspath($gallery);

			// Perhaps a filename was given instead of base64 data?
			if ($data[0] == '/' && @file_exists($data)) {
				if (!$filename) $filename = basename($data);
				$data = file_get_contents($data);
			}

			// Determine filenames
			$filename = $filename ? sanitize_title_with_dashes($filename) : uniqid('nextgen-gallery');
			if (preg_match("/\-(png|jpg|gif|jpeg)$/i", $filename, $match)) {
				$filename = str_replace($match[0], '.'.$match[1], $filename);
			}
			$abs_filename = path_join($upload_dir, $filename);

			// Create or retrieve the image object
			$image	= NULL;
			if ($image_id) {
				$image	= $this->object->_image_mapper->find($image_id, TRUE);
				unset($image->meta_data['saved']);
			}
			if (!$image) $image = $this->object->_image_mapper->create();
			$retval	= $image;
			
			// Create or update the database record
			$image->alttext		= sanitize_title_with_dashes(basename($filename, '.' . pathinfo($filename, PATHINFO_EXTENSION)));
			$image->galleryid	= $this->object->_get_gallery_id($gallery);
			$image->filename	= $filename;
			$image->image_slug = nggdb::get_unique_slug( sanitize_title_with_dashes( $image->alttext ), 'image' );
			$image_key			= $this->object->_image_mapper->get_primary_key_column();

            // If we can't write to the directory, then there's no point in continuing
            if (!@file_exists($upload_dir)) @wp_mkdir_p($upload_dir);
            if (!is_writable($upload_dir)) {
                throw new E_InsufficientWriteAccessException(
                    FALSE, $upload_dir, FALSE
                );
            }

			// Save the image
			if (($image_id = $this->object->_image_mapper->save($image))) {
				try {
					// Try writing the image
					$fp = fopen($abs_filename, 'w');
					fwrite($fp, $data);
					fclose($fp);

                    if ($settings->imgBackup)
                        $this->object->backup_image($image);

                    if ($settings->imgAutoResize)
                        $this->object->generate_image_clone(
                            $abs_filename,
                            $abs_filename,
                            $this->object->get_image_size_params($image_id, 'full')
                        );

                    // Ensure that fullsize dimensions are added to metadata array
                    $dimensions = getimagesize($abs_filename);
                    $full_meta = array(
                        'width'		=>	$dimensions[0],
                        'height'	=>	$dimensions[1]
                    );
                    if (!isset($image->meta_data) OR (is_string($image->meta_data) && strlen($image->meta_data) == 0)) {
                        $image->meta_data = array();
                    }
                    $image->meta_data = array_merge($image->meta_data, $full_meta);
                    $image->meta_data['full'] = $full_meta;

					// Generate a thumbnail for the image
					$this->object->generate_thumbnail($image);

                    // Set gallery preview image if missing
                    $this->object->get_registry()->get_utility('I_Gallery_Mapper')->set_preview_image($gallery, $image_id, TRUE);

					// Notify other plugins that an image has been added
					do_action('ngg_added_new_image', $image);

					// delete dirsize after adding new images
					delete_transient( 'dirsize_cache' );

					// Seems redundant to above hook. Maintaining for legacy purposes
					do_action(
						'ngg_after_new_images_added',
						$gallery_id,
						array($image->$image_key)
					);
				}
				catch(E_No_Image_Library_Exception $ex) {
						throw $ex;
				}
				catch(E_Clean_Exit $ex) {
					// pass
				}
				catch(Exception $ex) {
					throw new E_InsufficientWriteAccessException(
						FALSE, $abs_filename, FALSE, $ex
					);
				}
			}
            else throw new E_InvalidEntityException();
		}
		else throw new E_EntityNotFoundException();

        @ini_set('memory_limit', $memory_limit.'M');

		return $retval;
	}

    function import_gallery_from_fs($abspath, $gallery_id=FALSE, $move_files=TRUE)
    {
        $retval = FALSE;
        if (@file_exists($abspath)) {

            // Ensure that this folder has images
            $files_all = scandir($abspath);
            $files = array();
            
            // first perform some filtering on file list
            foreach ($files_all as $file)
            {
            	if ($file == '.' || $file == '..')
            		continue;
            		
            	$files[] = $file;
            }
            
            if (!empty($files)) {

                // Get needed utilities
                $fs = $this->get_registry()->get_utility('I_Fs');
                $gallery_mapper = $this->get_registry()->get_utility('I_Gallery_Mapper');

                // Sometimes users try importing a directory, which actually has all images under another directory
                $first_file_abspath = $fs->join_paths($abspath, $files[0]);
                if (is_dir($first_file_abspath) && count($files) == 1) return $this->import_gallery_from_fs($first_file_abspath, $gallery_id, $move_files);

                // If no gallery has been specified, then use the directory name as the gallery name
                if (!$gallery_id) {
                    // Create the gallery
                    $gallery = $gallery_mapper->create(array(
                        'title'         =>  basename($abspath),
                    ));
                    
                    if (!$move_files) {
                    	$gallery->path = str_ireplace(ABSPATH, '', $abspath);
                    }

                    // Save the gallery
                    if ($gallery->save()) $gallery_id = $gallery->id();
                }

                // Ensure that we have a gallery id
                if ($gallery_id) {
                    $retval = array('gallery_id' => $gallery_id, 'image_ids' => array());
                    foreach ($files as $file) {
                        if (!preg_match("/\.(jpg|jpeg|gif|png)/i", $file)) continue;
                        $file_abspath = $fs->join_paths($abspath, $file);
                        $image = null;
                        
                        if ($move_files) {
		                      $image = $this->object->upload_base64_image(
		                          $gallery_id,
		                          file_get_contents($file_abspath),
		                          str_replace(' ', '_', $file)
		                      );
                        }
                        else {
													// Create the database record ... TODO cleanup, some duplication here from upload_base64_image
													$factory = $this->object->get_registry()->get_utility('I_Component_Factory');
													$image = $factory->create('image');
													$image->alttext		= sanitize_title_with_dashes(basename($file_abspath, '.' . pathinfo($file_abspath, PATHINFO_EXTENSION)));
													$image->galleryid	= $this->object->_get_gallery_id($gallery_id);
													$image->filename	= basename($file_abspath);
													$image->image_slug = nggdb::get_unique_slug( sanitize_title_with_dashes( $image->alttext ), 'image' );
													$image_key			= $this->object->_image_mapper->get_primary_key_column();
													$abs_filename = $file_abspath;

													if (($image_id = $this->object->_image_mapper->save($image))) {
														try {
															// backup and image resizing should have already been performed, better to avoid
#															if ($settings->imgBackup)
#															    $this->object->backup_image($image);

#															if ($settings->imgAutoResize)
#															    $this->object->generate_image_clone(
#															        $abs_filename,
#															        $abs_filename,
#															        $this->object->get_image_size_params($image_id, 'full')
#															    );

															// Ensure that fullsize dimensions are added to metadata array
															$dimensions = getimagesize($abs_filename);
															$full_meta = array(
															    'width'		=>	$dimensions[0],
															    'height'	=>	$dimensions[1]
															);
															if (!isset($image->meta_data) OR (is_string($image->meta_data) && strlen($image->meta_data) == 0)) {
															    $image->meta_data = array();
															}
															$image->meta_data = array_merge($image->meta_data, $full_meta);
															$image->meta_data['full'] = $full_meta;

															// Generate a thumbnail for the image
															$this->object->generate_thumbnail($image);

															// Set gallery preview image if missing
															$this->object->get_registry()->get_utility('I_Gallery_Mapper')->set_preview_image($gallery, $image_id, TRUE);

															// Notify other plugins that an image has been added
															do_action('ngg_added_new_image', $image);

															// delete dirsize after adding new images
															delete_transient( 'dirsize_cache' );

															// Seems redundant to above hook. Maintaining for legacy purposes
															do_action(
																'ngg_after_new_images_added',
																$gallery_id,
																array($image->$image_key)
															);
														}
														catch(Exception $ex) {
															throw new E_InsufficientWriteAccessException(
																FALSE, $abs_filename, FALSE, $ex
															);
														}
													}
													else throw new E_InvalidEntityException();
                    	}
				                    	
                      $retval['image_ids'][] = $image->{$image->id_field};
                    }

                    // Add the gallery name to the result
                    $gallery = $gallery_mapper->find($gallery_id);
                    $retval['gallery_name'] = $gallery->title;
                    unset($gallery);
                }
            }
        }

        return $retval;
    }

	function get_image_format_list()
	{
		$format_list = array(IMAGETYPE_GIF => 'gif', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');

		return $format_list;
	}

	/**
	 * Returns an array of properties of a resulting clone image if and when generated
	 * @param string $image_path
	 * @param string $clone_path
	 * @param array $params
	 * @return array
	 */
	function calculate_image_clone_result($image_path, $clone_path, $params)
	{
		$width      = isset($params['width'])      ? $params['width']      : NULL;
		$height     = isset($params['height'])     ? $params['height']     : NULL;
		$quality    = isset($params['quality'])    ? $params['quality']    : NULL;
		$type       = isset($params['type'])       ? $params['type']       : NULL;
		$crop       = isset($params['crop'])       ? $params['crop']       : NULL;
		$watermark  = isset($params['watermark'])  ? $params['watermark']  : NULL;
		$rotation   = isset($params['rotation'])   ? $params['rotation']   : NULL;
		$reflection = isset($params['reflection']) ? $params['reflection'] : NULL;
		$crop_frame = isset($params['crop_frame']) ? $params['crop_frame'] : NULL;
		$result  = NULL;

		// Ensure we have a valid image
		if ($image_path && @file_exists($image_path))
		{
			// Ensure target directory exists, but only create 1 subdirectory
			$image_dir = dirname($image_path);
			$clone_dir = dirname($clone_path);
			$image_extension = pathinfo($image_path, PATHINFO_EXTENSION);
			$image_extension_str = null;
			$clone_extension = pathinfo($clone_path, PATHINFO_EXTENSION);
			$clone_extension_str = null;

			if ($image_extension != null)
			{
				$image_extension_str = '.' . $image_extension;
			}

			if ($clone_extension != null)
			{
				$clone_extension_str = '.' . $clone_extension;
			}

			$image_basename = basename($image_path, $image_extension_str);
			$clone_basename = basename($clone_path, $clone_extension_str);
			// We use a default suffix as passing in null as the suffix will make WordPress use a default
			$clone_suffix = null;
			$format_list = $this->object->get_image_format_list();
			$clone_format = null; // format is determined below and based on $type otherwise left to null

			// suffix is only used to reconstruct paths for image_resize function
			if (strpos($clone_basename, $image_basename) === 0)
			{
				$clone_suffix = substr($clone_basename, strlen($image_basename));
			}

			if ($clone_suffix != null && $clone_suffix[0] == '-')
			{
				// WordPress adds '-' on its own
				$clone_suffix = substr($clone_suffix, 1);
			}

            // Get original image dimensions
			$dimensions = getimagesize($image_path);

			if ($width == null && $height == null) {
				if ($dimensions != null) {

					if ($width == null) {
						$width = $dimensions[0];
					}

					if ($height == null) {
						$height = $dimensions[1];
					}
				}
				else {
					// XXX Don't think there's any other option here but to fail miserably...use some hard-coded defaults maybe?
					return null;
				}
			}

			if ($dimensions != null) {
				$dimensions_ratio = $dimensions[0] / $dimensions[1];

				if ($width == null) {
					$width = (int) round($height * $dimensions_ratio);

					if ($width == ($dimensions[0] - 1))
					{
						$width = $dimensions[0];
					}
				}
				else if ($height == null) {
					$height = (int) round($width / $dimensions_ratio);

					if ($height == ($dimensions[1] - 1))
					{
						$height = $dimensions[1];
					}
				}

				if ($width > $dimensions[0]) {
					$width = $dimensions[0];
				}

				if ($height > $dimensions[1]) {
					$height = $dimensions[1];
				}

				$image_format = $dimensions[2];

				if ($type != null)
				{
					if (is_string($type))
					{
						$type = strtolower($type);

						// Indexes in the $format_list array correspond to IMAGETYPE_XXX values appropriately
						if (($index = array_search($type, $format_list)) !== false)
						{
							$type = $index;

							if ($type != $image_format)
							{
								// Note: this only changes the FORMAT of the image but not the extension
								$clone_format = $type;
							}
						}
					}
				}
			}

			if ($width == null || $height == null) {
				// Something went wrong...
				return null;
			}

			$result['clone_path'] = $clone_path;
			$result['clone_directory'] = $clone_dir;
			$result['clone_suffix'] = $clone_suffix;
			$result['clone_format'] = $clone_format;
			$result['base_width'] = $dimensions[0];
			$result['base_height'] = $dimensions[1];

			// image_resize() has limitations:
			// - no easy crop frame support
			// - fails if the dimensions are unchanged
			// - doesn't support filename prefix, only suffix so names like thumbs_original_name.jpg for $clone_path are not supported
			//   also suffix cannot be null as that will make WordPress use a default suffix...we could use an object that returns empty string from __toString() but for now just fallback to ngg generator
            if (FALSE) { // disabling the WordPress method for Iteration #6
//			if (($crop_frame == null || !$crop) && ($dimensions[0] != $width && $dimensions[1] != $height) && $clone_suffix != null)
				$result['method'] = 'wordpress';

				$new_dims = image_resize_dimensions($dimensions[0], $dimensions[1], $width, $height, $crop);

				if ($new_dims) {
					list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $new_dims;

					$width = $dst_w;
					$height = $dst_h;
				}
				else {
					$result['error'] = new WP_Error( 'error_getting_dimensions', __('Could not calculate resized image dimensions') );
				}
			}
			else
			{
				$result['method'] = 'nextgen';
				$original_width = $dimensions[0];
				$original_height = $dimensions[1];
				$original_ratio = $original_width / $original_height;

				$aspect_ratio = $width / $height;

				$orig_ratio_x = $original_width / $width;
				$orig_ratio_y = $original_height / $height;

				if ($crop)
				{
					$algo = 'shrink'; // either 'adapt' or 'shrink'

					if ($crop_frame != null)
					{
						$crop_x = (int) round($crop_frame['x']);
						$crop_y = (int) round($crop_frame['y']);
						$crop_width = (int) round($crop_frame['width']);
						$crop_height = (int) round($crop_frame['height']);
						$crop_final_width = (int) round($crop_frame['final_width']);
						$crop_final_height = (int) round($crop_frame['final_height']);

						$crop_width_orig = $crop_width;
						$crop_height_orig = $crop_height;

						$crop_factor_x = $crop_width / $crop_final_width;
						$crop_factor_y = $crop_height / $crop_final_height;

						$crop_ratio_x = $crop_width / $width;
						$crop_ratio_y = $crop_height / $height;

						if ($algo == 'adapt')
						{
							// XXX not sure about this...don't use for now
#							$crop_width = (int) round($width * $crop_factor_x);
#							$crop_height = (int) round($height * $crop_factor_y);
						}
						else if ($algo == 'shrink')
						{
							if ($crop_ratio_x < $crop_ratio_y)
							{
								$crop_width = max($crop_width, $width);
								$crop_height = (int) round($crop_width / $aspect_ratio);
							}
							else
							{
								$crop_height = max($crop_height, $height);
								$crop_width = (int) round($crop_height * $aspect_ratio);
							}

							if ($crop_width == ($crop_width_orig - 1))
							{
								$crop_width = $crop_width_orig;
							}

							if ($crop_height == ($crop_height_orig - 1))
							{
								$crop_height = $crop_height_orig;
							}
						}

						$crop_diff_x = (int) round(($crop_width_orig - $crop_width) / 2);
						$crop_diff_y = (int) round(($crop_height_orig - $crop_height) / 2);

						$crop_x += $crop_diff_x;
						$crop_y += $crop_diff_y;

						$crop_max_x = ($crop_x + $crop_width);
						$crop_max_y = ($crop_y + $crop_height);

						// Check if we're overflowing borders
						//
						if ($crop_x < 0)
						{
							$crop_x = 0;
						}
						else if ($crop_max_x > $original_width)
						{
							$crop_x -= ($crop_max_x - $original_width);
						}

						if ($crop_y < 0)
						{
							$crop_y = 0;
						}
						else if ($crop_max_y > $original_height)
						{
							$crop_y -= ($crop_max_y - $original_height);
						}
					}
					else
					{
						if ($orig_ratio_x < $orig_ratio_y)
						{
							$crop_width = $original_width;
							$crop_height = (int) round($height * $orig_ratio_x);

						}
						else
						{
							$crop_height = $original_height;
							$crop_width = (int) round($width * $orig_ratio_y);
						}

						if ($crop_width == ($width - 1))
						{
							$crop_width = $width;
						}

						if ($crop_height == ($height - 1))
						{
							$crop_height = $height;
						}

						$crop_x = (int) round(($original_width - $crop_width) / 2);
						$crop_y = (int) round(($original_height - $crop_height) / 2);
					}

					$result['crop_area'] = array('x' => $crop_x, 'y' => $crop_y, 'width' => $crop_width, 'height' => $crop_height);
				}
				else {
					// Just constraint dimensions to ensure there's no stretching or deformations
					list($width, $height) = wp_constrain_dimensions($original_width, $original_height, $width, $height);
				}
			}

			$result['width'] = $width;
			$result['height'] = $height;
			$result['quality'] = $quality;

			$real_width = $width;
			$real_height = $height;

			if ($rotation && in_array(abs($rotation), array(90, 270)))
			{
				$real_width = $height;
				$real_height = $width;
			}

			if ($reflection)
			{
				// default for nextgen was 40%, this is used in generate_image_clone as well
				$reflection_amount = 40;
				// Note, round() would probably be best here but using the same code that C_NggLegacy_Thumbnail uses for compatibility
        $reflection_height = intval($real_height * ($reflection_amount / 100));
        $real_height = $real_height + $reflection_height;
			}

			$result['real_width'] = $real_width;
			$result['real_height'] = $real_height;
		}

		return $result;
	}

	/**
	 * Returns an array of dimensional properties (width, height, real_width, real_height) of a resulting clone image if and when generated
	 * @param string $image_path
	 * @param string $clone_path
	 * @param array $params
	 * @return array
	 */
	function calculate_image_clone_dimensions($image_path, $clone_path, $params)
	{
		$retval = null;
		$result = $this->object->calculate_image_clone_result($image_path, $clone_path, $params);

		if ($result != null) {
			$retval = array(
				'width' => $result['width'],
				'height' => $result['height'],
				'real_width' => $result['real_width'],
				'real_height' => $result['real_height']
			);
		}

		return $retval;
	}

	/**
	 * Generates a "clone" for an existing image, the clone can be altered using the $params array
	 * @param string $image_path
	 * @param string $clone_path
	 * @param array $params
	 * @return object
	 */
	function generate_image_clone($image_path, $clone_path, $params)
	{
		$width      = isset($params['width'])      ? $params['width']      : NULL;
		$height     = isset($params['height'])     ? $params['height']     : NULL;
		$quality    = isset($params['quality'])    ? $params['quality']    : NULL;
		$type       = isset($params['type'])       ? $params['type']       : NULL;
		$crop       = isset($params['crop'])       ? $params['crop']       : NULL;
		$watermark  = isset($params['watermark'])  ? $params['watermark']  : NULL;
		$reflection = isset($params['reflection']) ? $params['reflection'] : NULL;
		$rotation   = isset($params['rotation']) ? $params['rotation'] : NULL;
		$flip   = isset($params['flip']) ? $params['flip'] : NULL;
		$crop_frame = isset($params['crop_frame']) ? $params['crop_frame'] : NULL;
		$destpath   = NULL;
		$thumbnail  = NULL;
		$quality	= 100;

        // Do this before anything else can modify the original -- $detailed_size
        // may hold IPTC metadata we need to write to our clone
        $size = getimagesize($image_path, $detailed_size);

		$result = $this->object->calculate_image_clone_result($image_path, $clone_path, $params);

		// XXX this should maybe be removed and extra settings go into $params?
		$settings = C_NextGen_Settings::get_instance();

		// Ensure we have a valid image
		if ($image_path && @file_exists($image_path) && $result != null && !isset($result['error']))
		{
			$image_dir = dirname($image_path);
			$clone_path = $result['clone_path'];
			$clone_dir = $result['clone_directory'];
			$clone_suffix = $result['clone_suffix'];
			$clone_format = $result['clone_format'];
			$format_list = $this->object->get_image_format_list();

			// Ensure target directory exists, but only create 1 subdirectory
			if (!@file_exists($clone_dir))
			{
				if (strtolower(realpath($image_dir)) != strtolower(realpath($clone_dir)))
				{
					if (strtolower(realpath($image_dir)) == strtolower(realpath(dirname($clone_dir))))
					{
						wp_mkdir_p($clone_dir);
					}
				}
			}

			$method = $result['method'];
			$width = $result['width'];
			$height = $result['height'];
			$quality = $result['quality'];
			
			if ($quality == null)
			{
				$quality = 100;
			}

			if ($method == 'wordpress')
			{
                $original = wp_get_image_editor($image_path);
                $destpath = $clone_path;
                if (!is_wp_error($original))
                {
                    $original->resize($width, $height, $crop);
                    $original->set_quality($quality);
                    $original->save($clone_path);
                }
			}
			else if ($method == 'nextgen')
			{
				$destpath = $clone_path;
				$thumbnail = new C_NggLegacy_Thumbnail($image_path, true);

				if ($crop) {
					$crop_area = $result['crop_area'];
					$crop_x = $crop_area['x'];
					$crop_y = $crop_area['y'];
					$crop_width = $crop_area['width'];
					$crop_height = $crop_area['height'];

					$thumbnail->crop($crop_x, $crop_y, $crop_width, $crop_height);
				}

				$thumbnail->resize($width, $height);
			}

			// We successfully generated the thumbnail
			if (is_string($destpath) && (@file_exists($destpath) || $thumbnail != null))
			{
				if ($clone_format != null)
				{
					if (isset($format_list[$clone_format]))
					{
						$clone_format_extension = $format_list[$clone_format];
						$clone_format_extension_str = null;

						if ($clone_format_extension != null)
						{
							$clone_format_extension_str = '.' . $clone_format_extension;
						}

						$destpath_info = pathinfo($destpath);
						$destpath_extension = $destpath_info['extension'];
						$destpath_extension_str = null;

						if ($destpath_extension != null)
						{
							$destpath_extension_str = '.' . $destpath_extension;
						}

						if (strtolower($destpath_extension) != strtolower($clone_format_extension))
						{
							$destpath_dir = $destpath_info['dirname'];
							$destpath_basename = $destpath_info['filename'];
							$destpath_new = $destpath_dir . DIRECTORY_SEPARATOR . $destpath_basename . $clone_format_extension_str;

							if ((@file_exists($destpath) && rename($destpath, $destpath_new)) || $thumbnail != null)
							{
								$destpath = $destpath_new;
							}
						}
					}
				}

				if (is_null($thumbnail))
				{
					$thumbnail = new C_NggLegacy_Thumbnail($destpath, true);
				}
				else
				{
					$thumbnail->fileName = $destpath;
				}

				// This is quite odd, when watermark equals int(0) it seems all statements below ($watermark == 'image') and ($watermark == 'text') both evaluate as true
				// so we set it at null if it evaluates to any null-like value
				if ($watermark == null)
				{
					$watermark = null;
				}
				
				if ($watermark == 1 || $watermark === true)
				{
					if (in_array(strval($settings->wmType), array('image', 'text')))
					{
						$watermark = $settings->wmType;
					}
					else
					{
						$watermark = 'text';
					}
				}
				
				$watermark = strval($watermark);

				if ($watermark == 'image')
				{
					$thumbnail->watermarkImgPath = $settings['wmPath'];
					$thumbnail->watermarkImage($settings['wmPos'], $settings['wmXpos'], $settings['wmYpos']);
				}
				else if ($watermark == 'text')
				{
					$thumbnail->watermarkText = $settings['wmText'];
					$thumbnail->watermarkCreateText($settings['wmColor'], $settings['wmFont'], $settings['wmSize'], $settings['wmOpaque']);
					$thumbnail->watermarkImage($settings['wmPos'], $settings['wmXpos'], $settings['wmYpos']);
				}

				if ($rotation && in_array(abs($rotation), array(90, 180, 270)))
				{
					$thumbnail->rotateImageAngle($rotation);
				}

				$flip = strtolower($flip);

				if ($flip && in_array($flip, array('h', 'v', 'hv')))
				{
					$flip_h = in_array($flip, array('h', 'hv'));
					$flip_v = in_array($flip, array('v', 'hv'));

					$thumbnail->flipImage($flip_h, $flip_v);
				}

				if ($reflection)
				{
					$thumbnail->createReflection(40, 40, 50, FALSE, '#a4a4a4');
				}

				if ($clone_format != null && isset($format_list[$clone_format]))
				{
					// Force format
					$thumbnail->format = strtoupper($format_list[$clone_format]);
				}

				$thumbnail->save($destpath, $quality);
			}
		}

		return $thumbnail;
	}
}

class C_GalleryStorage_Driver_Base extends C_GalleryStorage_Base
{
    public static $_instances = array();

	function define($context)
	{
		parent::define($context);
		$this->add_mixin('Mixin_GalleryStorage_Driver_Base');
		$this->implement('I_GalleryStorage_Driver');
	}

	function initialize()
	{
		parent::initialize();
		$this->_gallery_mapper = $this->get_registry()->get_utility('I_Gallery_Mapper');
		$this->_image_mapper = $this->get_registry()->get_utility('I_Image_Mapper');
	}

    public static function get_instance($context = False)
    {
        if (!isset(self::$_instances[$context]))
        {
            self::$_instances[$context] = new C_GalleryStorage_Driver_Base($context);
        }
        return self::$_instances[$context];
    }


	/**
	 * Gets the class name of the driver used
	 * @return string
	 */
	function get_driver_class_name()
	{
		return get_called_class();
	}
}
