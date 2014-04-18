<?php

class A_Image_Options_Form extends Mixin
{
	function get_model()
	{
		return C_Settings_Model::get_instance();
	}

	function get_title()
	{
		return 'Image Options';
	}

	/**
	 * Returns the options available for sorting images
	 * @return array
	 */
	function _get_image_sorting_options()
	{
		return array(
			'Custom'					=>	'sortorder',
			'Image ID'					=>	'pid',
			'Filename'					=>	'filename',
			'Alt/Title Text'			=>	'alttext',
			'Date/Time'					=>	'imagedate'
		);
	}


	/**
	 * Returns the options available for sorting directions
	 * @return array
	 */
	function _get_sorting_direction_options()
	{
		return array(
			'Ascending'					=>	'ASC',
			'Descending'				=>	'DESC'
		);
	}


	/**
	 * Returns the options available for matching related images
	 */
	function _get_related_image_match_options()
	{
		return array(
			'Categories'				=>	'category',
			'Tags'						=>	'tags'
		);
	}
        
        /**
         * Tries to create the gallery storage directory if it doesn't exist
         * already
         * @return string
         */
        function _create_gallery_storage_dir($gallerypath=NULL)
        {
            $retval = TRUE;

            if (!$gallerypath) $gallerypath = $this->object->get_model()->get('gallerypath');
            $fs = $this->get_registry()->get_utility('I_Fs');
            $gallerypath = $fs->get_abspath($gallerypath);
            if (!@file_exists($gallerypath)) {
                @mkdir($gallerypath);
                $retval = @file_exists($gallerypath);
            }
            
            return $retval;
        }

        /*
         * Renders the form
         */
	function render()
	{
		if (!$this->object->_create_gallery_storage_dir()) {
			$this->object->get_model()->add_error( _('Gallery path does not exist and could not be created'), 'gallerypath');
		}
            
		$settings = $this->object->get_model();
		return $this->render_partial('photocrati-nextgen_other_options#image_options_tab', array(
			'gallery_path_label'			=>	_('Where would you like galleries stored?'),
			'gallery_path_help'				=>	_('Where galleries and their images are stored'),
			'gallery_path'					=>	$settings->gallerypath,
			'delete_image_files_label'		=>	_('Delete Image Files?'),
			'delete_image_files_help'		=>	_('When enabled, image files will be removed after a Gallery has been deleted'),
			'delete_image_files'			=>	$settings->deleteImg,
			'show_related_images_label'		=>	_('Show Related Images on Posts?'),
			'show_related_images_help'		=>	_('When enabled, related images will be appended to each post by matching the posts tags/categories to image tags'),
			'show_related_images'			=>	$settings->activateTags,
			'related_images_hidden_label'	=>	_('(Show Customization Settings)'),
			'related_images_active_label'	=>	_('(Hide Customization Settings)'),
			'match_related_images_label'	=>	_('How should related images be match?'),
			'match_related_images'			=>	$settings->appendType,
			'match_related_image_options'	=>	$this->object->_get_related_image_match_options(),
			'max_related_images_label'		=>	_('Maximum # of related images to display'),
			'max_related_images'			=>	$settings->maxImages,
			'related_images_heading_label'	=>	_('Heading for related images'),
			'related_images_heading'			=>	$settings->relatedHeading,
			'sorting_order_label'			=>	_("What's the default sorting method?"),
			'sorting_order_options'			=>	$this->object->_get_image_sorting_options(),
			'sorting_order'					=>	$settings->galSort,
			'sorting_direction_label'		=>	_('Sort in what direction?'),
			'sorting_direction_options'		=>	$this->object->_get_sorting_direction_options(),
			'sorting_direction'				=>	$settings->galSortDir,
			'automatic_resize_label'		=>	'Automatically resize images after upload',
			'automatic_resize_help'			=>	'It is recommended that your images be resized to be web friendly',
			'automatic_resize'				=>	$settings->imgAutoResize,
			'resize_images_label'			=>	_('What should images be resized to?'),
			'resize_images_help'			=>	_('After images are uploaded, they will be resized to the above dimensions and quality'),
			'resized_image_width_label'		=>	_('Width:'),
			'resized_image_height_label'	=>	_('Height:'),
			'resized_image_quality_label'	=>	_('Quality:'),
			'resized_image_width'			=>	$settings->imgWidth,
			'resized_image_height'			=>  $settings->imgHeight,
			'resized_image_quality'			=>	$settings->imgQuality,
			'backup_images_label'			=>	_('Backup the original images?'),
			'backup_images_yes_label'		=>	_('Yes'),
			'backup_images_no_label'		=>	_('No'),
			'backup_images'					=>	$settings->imgBackup
                    
		), TRUE);
	}

	function save_action($image_options)
	{
		$save = TRUE;
		if (($image_options)) {

			// Update the gallery path. Moves all images to the new location
			if (isset($image_options['gallerypath']) && (!is_multisite() || get_current_blog_id() == 1)) {
				$fs               = $this->get_registry()->get_utility('I_Fs');
				$original_dir     = $fs->get_abspath($this->object->get_model()->get('gallerypath'));
				$new_dir	  = $fs->get_abspath($image_options['gallerypath']);
        $image_options['gallerypath'] = $fs->add_trailing_slash($image_options['gallerypath']);

				// Note: the below file move is disabled because it's quite unreliable as it doesn't perform any checks
				//       For instance changing gallery path from /wp-content to /wp-content/gallery would attempt a recursive copy and then delete ALL files under wp-content, which would be disastreus
#				// If the gallery path has changed...
#				if ($original_dir != $new_dir) {

#                    // Try creating the new directory
#                    if ($this->object->_create_gallery_storage_dir($new_dir) AND is_writable($new_dir)) {

#					    // Try moving files
#						$this->object->recursive_copy($original_dir, $new_dir);
#						$this->object->recursive_delete($original_dir);

#						// Update gallery paths
#						$mapper = $this->get_registry()->get_utility('I_Gallery_Mapper');
#						foreach ($mapper->find_all() as $gallery) {
#							$gallery->path = $image_options['gallerypath'] . $gallery->name;
#							$mapper->save($gallery);
#						}
#					}
#					else {
#						$this->get_model()->add_error("Unable to change gallery path. Insufficient filesystem permissions");
#						$save = FALSE;
#					}
#				}
			}
			elseif (isset($image_options['gallerypath'])) {
				unset($image_options['gallerypath']);
			}

			// Update image options
			if ($save) $this->object->get_model()->set($image_options)->save();
		}
	}

	/**
	 * Copies one directory to another
	 * @param string $src
	 * @param string $dst
	 * @return boolean
	 */
	function recursive_copy($src, $dst)
	{
		$retval = TRUE;
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					if (!$this->object->recursive_copy($src . '/' . $file,$dst . '/' . $file)) {
						$retval = FALSE;
						break;
					}
				}
				else {
					if (!copy($src . '/' . $file,$dst . '/' . $file)) {
						$retval = FALSE;
						break;
					}
				}
			}
		}
		closedir($dir);
		return $retval;
	}

	/**
	 * Deletes all files within a particular directory
	 * @param string $dir
	 * @return boolean
	 */
	function recursive_delete($dir)
	{
		$retval = FALSE;
        $fp = opendir($dir);
		while(false !== ( $file = readdir($fp)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
                $file = $dir.'/'.$file;
				if ( is_dir($file) ) {
					$retval = $this->object->recursive_delete($file);
				}
				else {
					$retval = unlink($file);
				}
			}
		}
        closedir($fp);
        @rmdir($dir);
		return $retval;
	}
}
