<?php
/**
 {
	Module:		photocrati-attach_to_post,
	Depends:	{ photocrati-nextgen_gallery_display }
 }
 */

define('NEXTGEN_GALLERY_ATTACH_TO_POST_SLUG', 'ngg_attach_to_post');

class M_Attach_To_Post extends C_Base_Module
{
	var $attach_to_post_tinymce_plugin  = 'NextGEN_AttachToPost';
    var $_event_publisher               = NULL;

	/**
	 * Defines the module
	 * @param string|bool $context
	 */
    function define($context=FALSE)
    {
        parent::define(
			'photocrati-attach_to_post',
			'Attach To Post',
			'Provides the "Attach to Post" interface for displaying galleries and albums',
			'0.8',
			'http://www.nextgen-gallery.com',
			'Photocrati Media',
			'http://www.photocrati.com',
		    $context
		);

		include_once('class.attach_to_post_option_handler.php');
		C_NextGen_Settings::add_option_handler('C_Attach_To_Post_Option_Handler', array(
			'attach_to_post_url',
			'gallery_preview_url',
			'attach_to_post_display_tab_js_url'
		));

		include_once('class.attach_to_post_installer.php');
		C_Photocrati_Installer::add_handler($this->module_id, 'C_Attach_To_Post_Installer');
		
		$uri = strtolower($_SERVER['REQUEST_URI']);
		
		if (strpos($uri, '/nextgen-attach_to_post') !== false) {
			define('WP_ADMIN', true);
		}
    }

    /**
     * Gets the Frame Event Publisher
     * @return C_Component
     */
    function _get_frame_event_publisher()
    {
        if (is_null($this->_event_publisher)) {
            $this->_event_publisher = $this->get_registry()->get_utility('I_Frame_Event_Publisher', 'attach_to_post');
        }

        return $this->_event_publisher;
    }


	/**
	 * Registers requires the utilites that this module provides
	 */
	function _register_utilities()
	{
		// This utility provides a controller that renders the
		// Attach to Post interface, used to manage Displayed Galleries
		$this->get_registry()->add_utility(
			'I_Attach_To_Post_Controller',
			'C_Attach_Controller'
//			'C_Attach_To_Post_Proxy_Controller'
		);
	}

	/**
	 * Registers the adapters that this module provides
	 */
	function _register_adapters()
	{
		// Installs the Attach to Post module
		$this->get_registry()->add_adapter(
			'I_Installer', 'A_Attach_To_Post_Installer'
		);

		// Provides routing for the Attach To Post interface
		$this->get_registry()->add_adapter(
			'I_Router', 'A_Attach_To_Post_Routes'
		);

		// Provides AJAX actions for the Attach To Post interface
		$this->get_registry()->add_adapter(
			'I_Ajax_Controller',   'A_Attach_To_Post_Ajax'
		);

		// Applies a post hook to the generate_thumbnail method of the
		// gallery storage component
		$this->get_registry()->add_adapter(
			'I_Gallery_Storage', 'A_Gallery_Storage_Frame_Event'
		);
	}


	function _register_hooks()
	{
		if (is_admin()) {
			add_action(
				'admin_enqueue_scripts',
				array(&$this, 'enqueue_static_resources'),
				1
			);
		}

		// Add hook to delete displayed galleries when removed from a post
		add_action('pre_post_update', array(&$this, 'locate_stale_displayed_galleries'));
		add_action('before_delete_post', array(&$this, 'locate_stale_displayed_galleries'));
		add_action('post_updated',	array(&$this, 'cleanup_displayed_galleries'));
		add_action('after_delete_post', array(&$this, 'cleanup_displayed_galleries'));

		// Add hook to subsitute displayed gallery placeholders
		add_filter('the_content', array(&$this, 'substitute_placeholder_imgs'), PHP_INT_MAX, 1);

		// Emit frame communication events
		add_action('ngg_created_new_gallery',	array(&$this, 'new_gallery_event'));
		add_action('ngg_after_new_images_added',array(&$this, 'images_added_event'));
		add_action('ngg_page_event',			array(&$this, 'nextgen_page_event'));
        add_action('ngg_manage_tags',           array(&$this, 'manage_tags_event'));
	}

	/**
     * Substitutes the gallery placeholder content with the gallery type frontend
     * view, returns a list of static resources that need to be loaded
     * @param string $content
     */
    function substitute_placeholder_imgs($content)
    {
		// Get some utilities
		$mapper = $this->get_registry()->get_utility('I_Displayed_Gallery_Mapper');
		$router	= $this->get_registry()->get_utility('I_Router');

		// To match ATP entries we compare the stored url against a generic path
		// We must check HTTP and HTTPS as well as permalink and non-permalink forms
		$preview_url = parse_url($router->join_paths(
			$router->remove_url_segment('index.php', $router->get_base_url()),
			'/nextgen-attach_to_post/preview'
		));
		$preview_url = preg_quote($preview_url['host'] . $preview_url['path'], '#');

		$alt_preview_url = parse_url($router->join_paths(
			$router->remove_url_segment('index.php', $router->get_base_url()),
			'index.php/nextgen-attach_to_post/preview'
		));
		$alt_preview_url = preg_quote($alt_preview_url['host'] . $alt_preview_url['path'], '#');

		// The placeholder MUST have a gallery instance id
		if (preg_match_all("#<img.*http(s)?://({$preview_url}|{$alt_preview_url})/id--(\\d+).*\\/>#mi", $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				// Find the displayed gallery
				$displayed_gallery_id = $match[3];
				$displayed_gallery = $mapper->find($displayed_gallery_id, TRUE);

				// Get the content for the displayed gallery
				$retval = '<p>'._('Invalid Displayed Gallery').'</p>';
				if ($displayed_gallery) {
					$renderer = $this->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
					$retval = $renderer->render($displayed_gallery, TRUE);
				}
				$content = str_replace($match[0], $retval, $content);
			}
		}

		return $content;
    }

	/**
	 * Enqueues static resources required by the Attach to Post interface
	 */
	function enqueue_static_resources()
	{
		$router = $this->get_registry()->get_utility('I_Router');

		// Enqueue resources needed at post/page level
		if (preg_match("/\/wp-admin\/(post|post-new)\.php$/", $_SERVER['SCRIPT_NAME'])) {
			$this->_enqueue_tinymce_resources();
			wp_enqueue_style(
				'ngg_attach_to_post_dialog', $router->get_static_url('photocrati-attach_to_post#attach_to_post_dialog.css')
			);
		}

		elseif (isset($_REQUEST['attach_to_post']) OR
		  (isset($_REQUEST['page']) && strpos($_REQUEST['page'], 'nggallery') !== FALSE)) {
			wp_enqueue_script('iframely', $router->get_static_url('photocrati-attach_to_post#iframely.js'));
			wp_enqueue_style('iframely',  $router->get_static_url('photocrati-attach_to_post#iframely.css'));
		}
	}


	/**
	 * Enqueues resources needed by the TinyMCE editor
	 */
	function _enqueue_tinymce_resources()
	{
        wp_localize_script(
			'media-editor',
			'nextgen_gallery_attach_to_post_url',
			C_NextGen_Settings::get_instance()->attach_to_post_url
		);

		// Registers our tinymce button and plugin for attaching galleries
        $security   = $this->get_registry()->get_utility('I_Security_Manager');
        $sec_actor  = $security->get_current_actor();
        $checks = array(
            $sec_actor->is_allowed('NextGEN Attach Interface'),
            $sec_actor->is_allowed('NextGEN Use TinyMCE')
        );
        if (!in_array(FALSE, $checks)) {
            if (get_user_option('rich_editing') == 'true') {
                add_filter('mce_buttons', array(&$this, 'add_attach_to_post_button'));
                add_filter('mce_external_plugins', array(&$this, 'add_attach_to_post_tinymce_plugin'));
            }
        }
	}


	/**
	 * Adds a TinyMCE button for the Attach To Post plugin
	 * @param array $buttons
	 * @returns array
	 */
	function add_attach_to_post_button($buttons)
	{
		array_push(
            $buttons,
            'separator',
            $this->attach_to_post_tinymce_plugin
        );
        return $buttons;
	}


	/**
	 * Adds the Attach To Post TinyMCE plugin
	 * @param array $plugins
	 * @return array
	 * @uses mce_external_plugins filter
	 */
	function add_attach_to_post_tinymce_plugin($plugins)
	{
		$router = $this->get_registry()->get_utility('I_Router');
		$plugins[$this->attach_to_post_tinymce_plugin] = $router->get_static_url('photocrati-attach_to_post#ngg_attach_to_post_tinymce_plugin.js');
		return $plugins;
	}


	/**
	 * Locates the ids of displayed galleries that have been
	 * removed from the post, and flags then for cleanup (deletion)
	 * @global array $displayed_galleries_to_cleanup
	 * @param int $post_id
	 */
	function locate_stale_displayed_galleries($post_id)
	{
		global $displayed_galleries_to_cleanup;
		$displayed_galleries_to_cleanup	= array();
		$post							= get_post($post_id);
		$gallery_preview_url			= C_NextGen_Settings::get_instance()->get('gallery_preview_url');
		$preview_url = preg_quote($gallery_preview_url, '#');
		if (preg_match_all("#{$preview_url}/id--(\d+)#", html_entity_decode($post->post_content), $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$preview_url = preg_quote($match[0], '/');
				// The post was edited, and the displayed gallery placeholder was removed
				if (isset($_REQUEST['post_content']) && (!preg_match("/{$preview_url}/", $_POST['post_content']))) {
					$displayed_galleries_to_cleanup[] = intval($match[1]);
				}
				// The post was deleted
				elseif (!isset($_REQUEST['action'])) {
					$displayed_galleries_to_cleanup[] = intval($match[1]);
				}
			}
		}
	}

	/**
	 * Deletes any displayed galleries that are no longer associated with
	 * a post/page
	 * @global array $displayed_galleries_to_cleanup
	 * @param int $post_id
	 */
	function cleanup_displayed_galleries($post_id)
	{
		global $displayed_galleries_to_cleanup;
		$mapper = $this->get_registry()->get_utility('I_Displayed_Gallery_Mapper');
		foreach ($displayed_galleries_to_cleanup as $id) $mapper->destroy($id);
	}


	/**
	 * Notify frames that a new gallery has been created
	 * @param int $gallery_id
	 */
	function new_gallery_event($gallery_id)
	{
        $gallery = $this->get_registry()->get_utility('I_Gallery_Mapper')->find($gallery_id);
		if ($gallery) {
			$this->_get_frame_event_publisher()->add_event(array(
				'event'		=>	'new_gallery',
				'gallery_id'=>	intval($gallery_id),
				'gallery_title'   =>  $gallery->title
			));
		}
	}

	/**
	 * Notifies a frame that images have been added to a gallery
	 * @param int $gallery_id
	 * @param array $image_ids
	 */
	function images_added_event($gallery_id, $image_ids=array())
	{
        $this->_get_frame_event_publisher()->add_event(array(
			'event'			=>	'images_added',
			'gallery_id'		=>	intval($gallery_id)
		));
	}

    /**
     * Notifies a frame that the tags have changed
     *
     * @param array $tags
     */
    function manage_tags_event($tags = array())
    {
        $this->_get_frame_event_publisher()->add_event(array(
            'event' => 'manage_tags',
            'tags' => $tags
        ));
    }

	/**
	 * Notifies a frame that an action has been performed on a particular
	 * NextGEN page
	 * @param array $event
	 */
	function nextgen_page_event($event)
	{
        $this->_get_frame_event_publisher()->add_event($event);
	}

    function get_type_list()
    {
        return array(
            'A_Attach_To_Post_Ajax' => 'adapter.attach_to_post_ajax.php',
            'C_Attach_To_Post_Installer' => 'class.attach_to_post_installer.php',
            'A_Attach_To_Post_Routes' => 'adapter.attach_to_post_routes.php',
            'A_Gallery_Storage_Frame_Event' => 'adapter.gallery_storage_frame_event.php',
            'C_Attach_Controller' => 'class.attach_controller.php',
			'C_Attach_To_Post_Proxy_Controller' => 'class.attach_to_post_proxy_controller.php',
            'I_Attach_To_Post_Controller' => 'interface.attach_to_post_controller.php',
            'Mixin_Attach_To_Post_Display_Tab' => 'mixin.attach_to_post_display_tab.php'
        );
    }
}

new M_Attach_To_Post();
