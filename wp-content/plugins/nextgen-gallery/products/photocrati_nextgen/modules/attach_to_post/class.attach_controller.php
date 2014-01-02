<?php

class C_Attach_Controller extends C_NextGen_Admin_Page_Controller
{
	static $_instances = array();
	var	   $_displayed_gallery;
	var    $_marked_scripts;
	var 	 $_is_rendering;

	static function &get_instance($context)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}

	function define($context)
	{
		if (!is_array($context)) $context = array($context);
		array_unshift($context, 'ngg_attach_to_post');
		parent::define($context);
		$this->add_mixin('Mixin_Attach_To_Post');
		$this->add_mixin('Mixin_Attach_To_Post_Display_Tab');
		$this->implement('I_Attach_To_Post_Controller');
	}

	function initialize()
	{
		parent::initialize();
		$this->_load_displayed_gallery();
		
		$this->_marked_scripts = array();
		
		if (did_action('wp_print_scripts')) {
			$this->_handle_scripts();
		}
		else {
			add_action('wp_print_scripts', array($this, '_handle_scripts'), 9999);
		}
	}
	
	function _handle_scripts()
	{
		if (is_admin() && $this->_is_rendering) 
		{
			global $wp_scripts;
	
			$queue = $wp_scripts->queue;
			$marked = $this->_marked_scripts;
			
			foreach ($marked as $tag => $value) {
				$this->_handle_script($tag, $queue);
			}

			foreach ($queue as $extra) {
				wp_dequeue_script($extra);
			}
		}
	}
	
	function _handle_script($tag, &$queue)
	{
		global $wp_scripts;
	
		$registered = $wp_scripts->registered;

		$idx = array_search($tag, $queue);
		if ($idx !== false) {
			unset($queue[$idx]);
		}
		
		if (isset($registered[$tag])) {
			$script = $registered[$tag];
			
			if ($script->deps) {
				foreach ($script->deps as $dep) {
					$this->_handle_script($dep, $queue);
				}
			}
		}
	}
}

class Mixin_Attach_To_Post extends Mixin
{
	function _load_displayed_gallery()
	{
		$mapper = $this->get_registry()->get_utility('I_Displayed_Gallery_Mapper');
		if (!($this->object->_displayed_gallery = $mapper->find($this->object->param('id'), TRUE))) {
			$this->object->_displayed_gallery = $mapper->create();
		}
	}
	
	function mark_script($script_tag)
	{
		$this->object->_marked_scripts[$script_tag] = true;
	}

	function enqueue_backend_resources()
	{
		$this->call_parent('enqueue_backend_resources');
        // Enqueue frame event publishing
		wp_enqueue_script('frame_event_publisher');
		$this->object->mark_script('frame_event_publisher');

		// Enqueue JQuery UI libraries
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script('jquery-ui-sortable');
    	wp_enqueue_script('jquery-ui-tooltip');
		wp_enqueue_script('ngg_tabs', $this->get_static_url('photocrati-attach_to_post#ngg_tabs.js'));
		$this->object->mark_script('jquery-ui-tabs');
		$this->object->mark_script('jquery-ui-sortable');
		$this->object->mark_script('jquery-ui-tooltip');
		$this->object->mark_script('ngg_tabs');

		// Ensure select2
		wp_enqueue_style('select2');
		wp_enqueue_script('select2');
		$this->object->mark_script('select2');

		// Ensure that the Photocrati AJAX library is loaded
		wp_enqueue_script('photocrati_ajax');
		$this->object->mark_script('photocrati_ajax');

		// Enqueue logic for the Attach to Post interface as a whole
		wp_enqueue_script(
			'ngg_attach_to_post', $this->get_static_url('photocrati-attach_to_post#attach_to_post.js')
		);
		wp_enqueue_style(
			'ngg_attach_to_post', $this->get_static_url('photocrati-attach_to_post#attach_to_post.css')
		);
		$this->object->mark_script('ngg_attach_to_post');

		// Enqueue backbone.js library, required by the Attach to Post display tab
		wp_enqueue_script('backbone'); // provided by WP
		$this->object->mark_script('backbone');

		// Ensure underscore sting, a helper utility
		wp_enqueue_script(
			'underscore.string',
			$this->get_static_url('photocrati-attach_to_post#underscore.string.js'),
			array('underscore'),
			'2.3.0'
		);
		$this->object->mark_script('underscore.string');

		// Enqueue the backbone app for the display tab
		$settings			= C_NextGen_Settings::get_instance();
		$preview_url		= $settings->gallery_preview_url;
		$display_tab_js_url	= $settings->attach_to_post_display_tab_js_url;
		if ($this->object->_displayed_gallery->id()) {
			$display_tab_js_url .= '/id--'.$this->object->_displayed_gallery->id();
		}

		wp_enqueue_script(
			'ngg_display_tab',
			$display_tab_js_url,
			array('backbone', 'underscore.string')
		);
		wp_localize_script(
			'ngg_display_tab',
			'ngg_displayed_gallery_preview_url',
			$settings->gallery_preview_url
		);
		$this->object->mark_script('ngg_display_tab');
		
		// TODO: for now mark Pro scripts to ensure they are enqueued properly, remove this after Pro upgrade with tagging added
		$display_types = array('photocrati-nextgen_pro_slideshow', 'photocrati-nextgen_pro_horizontal_filmstrip', 'photocrati-nextgen_pro_thumbnail_grid', 'photocrati-nextgen_pro_blog_gallery', 'photocrati-nextgen_pro_film');
		foreach ($display_types as $display_type) {
			$this->object->mark_script($display_type . '-js');
		}
		
		$this->object->mark_script('nextgen_pro_albums_settings_script');
	}

	/**
	 * Renders the interface
	 */
	function index_action($return=FALSE)
	{
        if ($this->object->_displayed_gallery->is_new()) $this->object->expires("+2 hour");
        
    $this->object->_is_rendering = true;
    
		// Enqueue resources
		return $this->object->render_view('photocrati-attach_to_post#attach_to_post', array(
			'page_title'	=>	$this->object->_get_page_title(),
			'tabs'			=>	$this->object->_get_main_tabs()
		), $return);
	}


	/**
	 * Displays a preview image for the displayed gallery
	 */
	function preview_action()
	{
		$found_preview_pic = FALSE;

		$dyn_thumbs		= $this->object->get_registry()->get_utility('I_Dynamic_Thumbnails_Manager');
		$storage		= $this->object->get_registry()->get_utility('I_Gallery_Storage');
		$image_mapper	= $this->object->get_registry()->get_utility('I_Image_Mapper');

		// Get the first entity from the displayed gallery. We will use this
		// for a preview pic
		$entity = array_pop($this->object->_displayed_gallery->get_included_entities(1));
		$image = FALSE;
		if ($entity) {
			// This is an album or gallery
			if (isset($entity->previewpic)) {
				$image = (int)$entity->previewpic;
				if (($image = $image_mapper->find($image))) {
						$found_preview_pic = TRUE;
				}
			}

			// Is this an image
			else if (isset($entity->galleryid)) {
				$image = $entity;
				$found_preview_pic = TRUE;
			}
		}

		// Were we able to find a preview pic? If so, then render it
        $image_size = $dyn_thumbs->get_size_name(array(
            'width'     =>  200,
            'height'    =>  200,
            'quality'   =>  90,
            'type'		=>	'jpg'
        ));;
		$found_preview_pic = $storage->render_image($image, $image_size, TRUE);

		// Render invalid image if no preview pic is found
		if (!$found_preview_pic) {
            $filename = $this->object->get_static_abspath('photocrati-attach_to_post#invalid_image.png');
			$this->set_content_type('image/png');
			readfile($filename);
			$this->render();
		}
	}

	/**
	 * Returns the page title of the Attach to Post interface
	 * @return string
	 */
	function _get_page_title()
	{
		return _('NextGEN Gallery - Attach To Post');
	}


	/**
	 * Returns the main tabs displayed on the Attach to Post interface
	 * @returns array
	 */
	function _get_main_tabs()
	{
        $retval = array();

        $security   = $this->get_registry()->get_utility('I_Security_Manager');
        $sec_actor  = $security->get_current_actor();

        if ($sec_actor->is_allowed('NextGEN Manage gallery')) {
            $retval['displayed_tab']    = array(
                'content'   => $this->object->_render_display_tab(),
                'title'     => _('Display Galleries')
            );
        }

        if ($sec_actor->is_allowed('NextGEN Upload images')) {
            $retval['create_tab']       = array(
                'content'   =>  $this->object->_render_create_tab(),
                'title'     =>  _('Add Gallery / Images')
            );
        }

        if ($sec_actor->is_allowed('NextGEN Manage others gallery') && $sec_actor->is_allowed('NextGEN Manage gallery')) {
            $retval['galleries_tab']    = array(
                'content'   =>  $this->object->_render_galleries_tab(),
                'title'     =>  _('Manage Galleries')
            );
        }

        if ($sec_actor->is_allowed('NextGEN Edit album')) {
            $retval['albums_tab']       = array(
                'content'   =>  $this->object->_render_albums_tab(),
                'title'     =>  _('Manage Albums')
            );
        }

        if ($sec_actor->is_allowed('NextGEN Manage tags')) {
            $retval['tags_tab']         = array(
                'content'   =>  $this->object->_render_tags_tab(),
                'title'     =>  _('Manage Tags')
            );
        }

		return $retval;
	}

	/**
	 * Renders a NextGen Gallery page in an iframe, suited for the attach to post
	 * interface
	 * @param string $page
	 * @return string
	 */
	function _render_ngg_page_in_frame($page, $tab_id = null)
	{
		$frame_url = admin_url("/admin.php?page={$page}&attach_to_post");
		$frame_url = nextgen_esc_url($frame_url);

		if ($tab_id) {
			$tab_id = " id='ngg-iframe-{$tab_id}'";
		}

		return "<iframe name='{$page}' frameBorder='0'{$tab_id} class='ngg-attach-to-post ngg-iframe-page-{$page}' scrolling='no' src='{$frame_url}'></iframe>";
	}

	/**
	 * Renders the display tab for adjusting how images/galleries will be
	 * displayed
	 * @return type
	 */
	function _render_display_tab()
	{
		return $this->object->render_partial('photocrati-attach_to_post#display_tab', array(
			'messages'	=>	array(),
			'tabs'		=>	$this->object->_get_display_tabs()
		), TRUE);
	}


	/**
	 * Renders the tab used primarily for Gallery and Image creation
	 * @return type
	 */
	function _render_create_tab()
	{
		return $this->object->_render_ngg_page_in_frame('ngg_addgallery', 'create_tab');
	}


	/**
	 * Renders the tab used for Managing Galleries
	 * @return string
	 */
	function _render_galleries_tab()
	{
		return $this->object->_render_ngg_page_in_frame('nggallery-manage-gallery', 'galleries_tab');
	}


	/**
	 * Renders the tab used for Managing Albums
	 */
	function _render_albums_tab()
	{
		return $this->object->_render_ngg_page_in_frame('nggallery-manage-album', 'albums_tab');
	}


	/**
	 * Renders the tab used for Managing Albums
	 * @return string
	 */
	function _render_tags_tab()
	{
		return $this->object->_render_ngg_page_in_frame('nggallery-tags', 'tags_tab');
	}
}
