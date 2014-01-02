<?php
// see : http://wordpress.org/support/topic/plugin-nextgen-gallery-ngg-and-featured-image-issue?replies=14
/**
 * nggPostThumbnail - Class for adding the post thumbnail feature
 * 
 * @package NextGEN Gallery
 * @author Alex Rabe 
 * 
 * @version 1.0.2
 * @access internal
 */
class nggPostThumbnail {

	/**
	 * PHP4 compatibility layer for calling the PHP5 constructor.
	 * 
	 */
	function nggPostThumbnail() {
		return $this->__construct();
	}

	/**
	 * Main constructor - Add filter and action hooks
	 * 
	 */	
	function __construct() {
		
		add_filter( 'admin_post_thumbnail_html', array( $this, 'admin_post_thumbnail'), 10, 2 );
		add_action( 'wp_ajax_ngg_set_post_thumbnail', array( $this, 'ajax_set_post_thumbnail') );
		// Adding filter for the new post_thumbnail
		add_filter( 'post_thumbnail_html', array( $this, 'ngg_post_thumbnail'), 10, 5 );
		return;		
	}

	/**
	 * Filter for the post meta box. look for a NGG image if the ID is "ngg-<imageID>"
	 * 
	 * @param string $content
	 * @return string html output
	 */
	function admin_post_thumbnail( $content, $post_id = null ) 
	{
    if ($post_id == null)
    {
			global $post;

		  if ( !is_object($post) )
		     return $content;
       
      $post_id = $post->ID;
    }
        
		$thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);

		// in the case it's a ngg image it return ngg-<imageID>
		if ( strpos($thumbnail_id, 'ngg-') === false) 
		{
			global $wp_version;
			
			if (version_compare($wp_version, '3.5', '>=') && $thumbnail_id <= 0)
			{
				$iframe_src = get_upload_iframe_src('image');
				$iframe_src = remove_query_arg('TB_iframe', $iframe_src);
				$iframe_src = add_query_arg('tab', 'nextgen', $iframe_src);
				$iframe_src = add_query_arg('chromeless', '1', $iframe_src);
				$iframe_src = add_query_arg('TB_iframe', '1', $iframe_src);
			
			  $set_thumbnail_link = '<p class="hide-if-no-js"><a title="' . esc_attr__( 'Set NextGEN featured image' ) . '" href="' . nextgen_esc_url( $iframe_src ) . '" id="set-ngg-post-thumbnail" class="thickbox">%s</a></p>';
			  
			  $content .= sprintf($set_thumbnail_link, esc_html__( 'Set NextGEN featured image' ));
			}
			
			return $content;
		}
			
		// cut off the 'ngg-'
		$thumbnail_id = substr( $thumbnail_id, 4);

		return $this->_wp_post_thumbnail_html( $thumbnail_id );		
	}
	
	/**
	 * Filter for the post content
	 * 
	 * @param string $html
	 * @param int $post_id
	 * @param int $post_thumbnail_id
	 * @param string|array $size Optional. Image size.  Defaults to 'thumbnail'.
	 * @param string|array $attr Optional. Query string or array of attributes.
	 * @return string html output
	 */
	function ngg_post_thumbnail( $html, $post_id, $post_thumbnail_id, $size = 'post-thumbnail', $attr = '' ) {

		global $post, $_wp_additional_image_sizes;

		// in the case it's a ngg image it return ngg-<imageID>
		if ( strpos($post_thumbnail_id, 'ngg-') === false)
			return $html;

		// cut off the 'ngg-'
		$post_thumbnail_id = substr( $post_thumbnail_id, 4);

		// get the options
		$ngg_options = nggGallery::get_option('ngg_options');

		// get the image data
		$image = nggdb::find_image($post_thumbnail_id);

		if (!$image) 
			return $html;

		$img_src = false;		
		$class = 'wp-post-image ngg-image-' . $image->pid . ' ';
        
        if (is_array($size) || is_array($_wp_additional_image_sizes) && isset($_wp_additional_image_sizes[$size])) {		        	        		
			$class .= isset($attr['class']) ? esc_attr($attr['class']) : '';
		
			if( is_array($size)){
				//the parameters is given as an array rather than a predfined image
				$width = absint( $size[0] );
				$height = absint( $size[1] );
				if(isset($size[2]) && $size[2] === true) {
					$mode = 'crop';
				} else if(isset($size[2])){
					$mode = $size[2];
				} else {
					$mode = '';					
				}
			} else {
				$width = absint( $_wp_additional_image_sizes[$size]['width'] );
				$height = absint( $_wp_additional_image_sizes[$size]['height'] );
        $mode = ($_wp_additional_image_sizes[$size]['crop']) ? 'crop' : '';
			}

      // check fo cached picture
          if ( $post->post_status == 'publish' )
              $img_src = $image->cached_singlepic_file( $width, $height, $mode );                
  
			// if we didn't use a cached image then we take the on-the-fly mode 
		        if ($img_src ==  false) 
		        	$img_src = trailingslashit( home_url() ) . 'index.php?callback=image&amp;pid=' . $image->pid . '&amp;width=' . $width . '&amp;height=' . $height . '&amp;mode=crop';
                
		} else {
			$img_src = $image->thumbURL;
		}
		
		$alttext = isset($attr['alt']) ? $attr['alt'] : $image->alttext;
		$titletext = isset($attr['title']) ? $attr['title'] : $image->title;

		$html = '<img src="' . esc_attr($img_src) . '" alt="' . esc_attr($alttext) . '" title="' . esc_attr($titletext) .'" class="'.$class.'" />';

		return $html;
	}
	
	/**
	 * nggPostThumbnail::ajax_set_post_thumbnail()
	 * 
	 * @return void
	 */
	function ajax_set_post_thumbnail() 
	{
		global $post_ID;

		// check for correct capability
		if ( !is_user_logged_in() )
			die( '-1' );

		// get the post id as global variable, otherwise the ajax_nonce failed later
		$post_ID = intval( $_POST['post_id'] );

		if ( !current_user_can( 'edit_post', $post_ID ) )
			die( '-1' );

		$thumbnail_id = intval( $_POST['thumbnail_id'] );

		// delete the image
		if ( $thumbnail_id == '-1' ) {
			delete_post_meta( $post_ID, '_thumbnail_id' );
			die('0');
		}

		if ($thumbnail_id != null)
		{
			$registry = C_Component_Registry::get_instance();
		  $imap = $registry->get_utility('I_Image_Mapper');
		  $storage  = $registry->get_utility('I_Gallery_Storage');
		  
		  $image = $imap->find($thumbnail_id);
		
			// for NGG we look for the image id
			if ($image)
			{
				$image_id = $thumbnail_id;
				
				$args = array(
					'post_type' => 'attachment',
					'meta_key' => '_ngg_image_id',
					'meta_compare' => '==',
					'meta_value' => $image_id
				);
				
				$upload_dir = wp_upload_dir();
				$basedir = $upload_dir['basedir'];
				$thumbs_dir = path_join($basedir, 'ngg_featured');
				$gallery_abspath = $storage->get_gallery_abspath($image->galleryid);
				$image_abspath = $storage->get_full_abspath($image);
				$target_path = null;
	
				$posts = get_posts($args);
				$attachment_id = null;
				
				if ($posts != null)
				{
					$attachment_id = $posts[0]->ID;
				}
				else
				{
					$url = $storage->get_full_url($image);
					
					$target_relpath = null;
					$target_basename = basename($image_abspath);
					
					if (strpos($image_abspath, $gallery_abspath) === 0)
					{
						$target_relpath = substr($image_abspath, strlen($gallery_abspath));
					}
					else if ($image->galleryid)
					{
						$target_relpath = path_join(strval($image->galleryid), $target_basename);
					}
					else
					{
						$target_relpath = $target_basename;
					}
					
					$target_relpath = trim($target_relpath, '\\/');
					$target_path = path_join($thumbs_dir, $target_relpath);
					$max_count = 100;
					$count = 0;
					
					while (file_exists($target_path) && $count <= $max_count)
					{
						$count++;
						
						$pathinfo = pathinfo($target_path);
						$dirname = $pathinfo['dirname'];
						$filename = $pathinfo['filename'];
						$extension = $pathinfo['extension'];
						
						$rand = mt_rand(1, 9999);
						$basename = $filename . '_' . sprintf('%04d', $rand) . '.' . $extension;
						
						$target_path = path_join($dirname, $basename);
					}
					
					if (file_exists($target_path))
					{
						// XXX handle very rare case in which $max_count wasn't enough?
					}
					
					$target_dir = dirname($target_path);
					
					wp_mkdir_p($target_dir);
					
					if (@copy($image_abspath, $target_path))
					{
						$size = @getimagesize($target_path);
						$image_type = ($size) ? $size['mime'] : 'image/jpeg';
				
						$title = sanitize_file_name($image->alttext);
						$caption = sanitize_file_name($image->description);
				
						$attachment = array(
							'post_title' => $title,
							'post_content' => $caption,
							'post_status' => 'attachment',
							'post_parent' => 0,
							'post_mime_type' => $image_type,
							'guid' => $url
						);

						// Save the data
						$attachment_id = wp_insert_attachment($attachment, $target_path);
				
						if ($attachment_id)
						{
							wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $target_path));
					
							update_post_meta($attachment_id, '_ngg_image_id', $image_id);
						}
					}
				}
			
				if ($attachment_id)
				{
					//$attachment = get_post($attachment_id);
					//$attachment_meta = wp_get_attachment_metadata($attachment_id);
					$attachment_file = get_attached_file($attachment_id);
					$target_path = $attachment_file;
					
					if (filemtime($image_abspath) > filemtime($target_path))
					{
						if (@copy($image_abspath, $target_path))
						{
							wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $target_path));
						}
					}
					
					die(strval($attachment_id));
				}
			}
		}
		
		die('0');
	}

	/**
	 * Output HTML for the post thumbnail meta-box.
	 *
	 * @see wp-admin\includes\post.php
	 * @param int $thumbnail_id ID of the image used for thumbnail
	 * @return string html output
	 */
	function _wp_post_thumbnail_html( $thumbnail_id = NULL ) {
	   
		global $_wp_additional_image_sizes, $post_ID;

	    $set_thumbnail_link = '<p class="hide-if-no-js"><a title="' . esc_attr__( 'Set featured image' ) . '" href="' . nextgen_esc_url( get_upload_iframe_src('image') ) . '" id="set-post-thumbnail" class="thickbox">%s</a></p>';
	    $content = sprintf($set_thumbnail_link, esc_html__( 'Set featured image' ));
		
        $image = nggdb::find_image($thumbnail_id);
        $img_src = false;

		// get the options
		$ngg_options = nggGallery::get_option('ngg_options');
        
		if ( $image ) {
            if ( is_array($_wp_additional_image_sizes) && isset($_wp_additional_image_sizes['post-thumbnail']) ){
                // Use post thumbnail settings if defined
     			$width = absint( $_wp_additional_image_sizes['post-thumbnail']['width'] );
    			$height = absint( $_wp_additional_image_sizes['post-thumbnail']['height'] );
                $mode = $_wp_additional_image_sizes['post-thumbnail']['crop'] ? 'crop' : '';
    		    // check fo cached picture
   		        $img_src = $image->cached_singlepic_file( $width, $height, $mode );                
            }

		    // if we didn't use a cached image then we take the on-the-fly mode 
		    if ( $img_src == false ) 
		        $img_src = trailingslashit( home_url() ) . 'index.php?callback=image&amp;pid=' . $image->pid . '&amp;width=' . $width . '&amp;height=' . $height . '&amp;mode=crop';
			
            $thumbnail_html = '<img width="266" src="'. $img_src . '" alt="'.$image->alttext.'" title="'.$image->alttext.'" />';
            
			if ( !empty( $thumbnail_html ) ) {
    			$ajax_nonce = wp_create_nonce( "set_post_thumbnail-$post_ID" );
    			$content = sprintf($set_thumbnail_link, $thumbnail_html);
    			$content .= '<p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail" onclick="WPRemoveThumbnail(\'' . $ajax_nonce . '\');return false;">' . esc_html__( 'Remove featured image' ) . '</a></p>';
			}
		}

		return $content;
	}	
	
}

$nggPostThumbnail = new nggPostThumbnail();
