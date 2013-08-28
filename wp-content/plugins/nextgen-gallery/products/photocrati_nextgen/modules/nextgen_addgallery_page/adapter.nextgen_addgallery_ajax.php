<?php

class A_NextGen_AddGallery_Ajax extends Mixin
{
	function cookie_dump_action()
	{
		return array('success' => 1);
	}

    function upload_image_action()
    {
        $retval = array();

        $gallery_id     = intval($this->param('gallery_id'));
        $gallery_name   = urldecode($this->param('gallery_name'));
        $error          = FALSE;
        
        if ($this->validate_ajax_request('nextgen_upload_image'))
        {
		      // We need to create a gallery
		      if ($gallery_id == 0) {
		          if (strlen($gallery_name) > 0) {
		              $gallery_mapper = $this->object->get_registry()->get_utility('I_Gallery_Mapper');
		              $gallery = $gallery_mapper->create(array(
		                  'title' =>  $gallery_name
		              ));
		              if (!$gallery->save()) {
		                  $retval['error'] = $gallery->get_errors();
		                  $error = TRUE;
		              }
		              else {
		                  $gallery_id = $gallery->id();
		              }
		          }
		          else {
		              $error = TRUE;
		              $retval['error'] = "No gallery name specified";
		          }
		      }

		      // Upload the image to the gallery
		      if (!$error) {
		          $retval['gallery_id'] = $gallery_id;
		          $storage = $this->object->get_registry()->get_utility('I_Gallery_Storage');

		          try{
		              if ($storage->is_zip()) {
		                  if (($results = $storage->upload_zip($gallery_id))) {
		                      $retval = $results;
		                  }
		                  else $retval['error'] = 'Failed to extract images from ZIP';
		              }
		              elseif (($image = $storage->upload_image($gallery_id))) {
		                  $retval['image_ids'] = array($image->id());
		              }
		              else {
		                  $retval['error'] = 'Image generation failed';
		                  $error = TRUE;
		              }
		          }
		          catch (E_InsufficientWriteAccessException $ex) {
		              $retval['error'] = $ex->getMessage();
		              $error = TRUE;
		          }
		          catch (Exception $ex) {
		              $retval['error']            = "An unexpected error occured.";
		              $retval['error_details']    = $ex->getMessage();
		              $error = TRUE;
		          }
		      }
		    }
		    else {
          $retval['error'] = "No permissions to upload images. Try refreshing the page.";
          $error = TRUE;
		    }

        if ($error) header('HTTP/1.1 400 Bad Request');
        else $retval['gallery_name'] = $gallery_name;

        return $retval;
    }


    function browse_folder_action()
    {
        $retval = array();
        $html = array();
        
        if ($this->validate_ajax_request('nextgen_upload_image'))
        {
		      if (($dir = urldecode($this->param('dir')))) {
		          $fs = $this->get_registry()->get_utility('I_Fs');
		          $root = path_join($fs->get_document_root(), 'wp-content');

		          $browse_path = $fs->join_paths($root, $dir);
		          if (@file_exists($browse_path)) {
		              $files = scandir($browse_path);
		              natcasesort($files);
		              if( count($files) > 2 ) { /* The 2 accounts for . and .. */
		                  $html[] = "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
		                  foreach( $files as $file ) {
		                      $file_path = path_join($browse_path, $file);
		                      $rel_file_path = str_replace(WP_CONTENT_DIR, '', $file_path);
		                      if(@file_exists($file_path) && $file != '.' && $file != '..' && is_dir($file_path) ) {
		                          $html[] = "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($rel_file_path) . "/\">" . htmlentities($file) . "</a></li>";
		                      }
		                  }
		                  $html[] = "</ul>";
		              }
		              $retval['html'] = implode("\n", $html);
		          }
		          else {
		              $retval['error'] = "Directory does not exist.";
		          }
		      }
		      else {
		          $retval['error'] = "No directory specified.";
		      }
	      }
        else {
          $retval['error'] = "No permissions to browse folders. Try refreshing the page.";
        }

        return $retval;
    }


    function import_folder_action()
    {
        $retval = array();

        if ($this->validate_ajax_request('nextgen_upload_image'))
        {
		      if (($folder = $this->param('folder'))) {
		          $storage = $this->get_registry()->get_utility('I_Gallery_Storage');
		          $fs      = $this->get_registry()->get_utility('I_Fs');
		          try {
		              $retval = $storage->import_gallery_from_fs($fs->join_paths($fs->get_document_root(), 'wp-content', $folder));
		              if (!$retval) $retval = array('error' => "Could not import folder. No images found.");
		          }
		          catch (Exception $ex) {
		              $retval['error'] = $ex->getMessage();
		          }
		      }
		      else {
		          $retval['error'] = "No folder specified";
		      }
        }
        else {
          $retval['error'] = "No permissions to import folders. Try refreshing the page.";
        }

        return $retval;
    }
		  
		function validate_ajax_request($action, $check_token = false)
		{
			$valid_request = false;
			$security = $this->get_registry()->get_utility('I_Security_Manager');
			$sec_actor = $security->get_current_actor();
			$sec_token = $security->get_request_token($action);
			
			if ($sec_actor->is_allowed($action) && (!$check_token || $sec_token->check_current_request()))
			{
				$valid_request = true;
			}

			return $valid_request;
		}
}
