<?php

/***
	{
		Module: photocrati-nextgen_gallery_display,
		Depends: { photocrati-simple_html_dom }
	}
***/

define('NEXTGEN_DISPLAY_SETTINGS_SLUG', 'ngg_display_settings');
define('NEXTGEN_DISPLAY_PRIORITY_BASE', 10000);
define('NEXTGEN_DISPLAY_PRIORITY_STEP', 2000);

class M_Gallery_Display extends C_Base_Module
{
	function define()
	{
		parent::define(
			'photocrati-nextgen_gallery_display',
			'Gallery Display',
			'Provides the ability to display gallery of images',
			'0.7',
			'http://www.photocrati.com',
			'Photocrati Media',
			'http://www.photocrati.com'
		);

		include_once('class.gallery_display_installer.php');
		C_Photocrati_Installer::add_handler($this->module_id, 'C_Gallery_Display_Installer');
	}


	/**
	 * Register utilities required for this module
	 */
	function _register_utilities()
	{
		// This utility provides a controller to render the settings form
		// for a display type, or render the front-end of a display type
		$this->get_registry()->add_utility(
			'I_Display_Type_Controller',
			'C_Display_Type_Controller'
		);

		// This utility provides a datamapper for Display Types
		$this->get_registry()->add_utility(
			'I_Display_Type_Mapper',
			'C_Display_Type_Mapper'
		);

		// This utility provides a datamapper for Displayed Galleries. A
		// displayed gallery is the association between some entities (images
		//or galleries) and a display type
		$this->get_registry()->add_utility(
			'I_Displayed_Gallery_Mapper',
			'C_Displayed_Gallery_Mapper'
		);

		// This utility provides a datamapper for Displayed Gallery Sources. A
		// source instructs a displayed gallery where the entities are to be
		// fetched from - e.g. galleries, albums, etc.
		$this->get_registry()->add_utility(
			'I_Displayed_Gallery_Source_Mapper',
			'C_Displayed_Gallery_Source_Mapper'
		);

        // This utility provides the capabilities of rendering a display type
        $this->get_registry()->add_utility(
            'I_Displayed_Gallery_Renderer',
            'C_Displayed_Gallery_Renderer'
        );
	}

	/**
	 * Registers adapters required for this module
	 */
	function _register_adapters()
	{
		// Provides factory methods for creating display type and
		// displayed gallery instances
		$this->get_registry()->add_adapter(
			'I_Component_Factory', 'A_Gallery_Display_Factory'
		);

        if (is_admin()) {
            $this->get_registry()->add_adapter(
                'I_Page_Manager',
                'A_Display_Settings_Page'
            );
        }

		$this->_get_registry()->add_adapter(
			'I_Ajax_Controller',
			'A_Gallery_Display_Ajax'
		);

		$this->get_registry()->add_adapter('I_MVC_View', 'A_Gallery_Display_View');
	}

	/**
	 * Registers hooks for the WordPress framework
	 */
	function _register_hooks()
	{
		// Add a shortcode for displaying galleries
		C_NextGen_Shortcode_Manager::add('ngg_images', array(&$this, 'display_images'));
        add_action('init', array(&$this, '_register_resources'));
        add_action('admin_bar_menu', array(&$this, 'add_admin_bar_menu'), 100);
        add_filter('the_content', array($this, '_render_related_images'));
		add_action('wp_enqueue_scripts', array(&$this, 'no_resources_mode'), PHP_INT_MAX-1);
		add_filter('run_ngg_resource_manager', array(&$this, 'no_resources_mode'));
	}


	function no_resources_mode($valid_request=TRUE)
	{
		if (isset($_REQUEST['ngg_no_resources'])) {
			global $wp_scripts, $wp_styles;

			// Don't enqueue any stylesheets
			if ($wp_scripts)
				$wp_scripts->queue = $wp_styles->queue = array();

			// Don't do any actions that we don't have to
//			remove_all_actions('wp_loaded');
//			remove_all_actions('get_header');
//			remove_all_actions('wp_head');
//			remove_all_actions('get_search_form');
//			remove_all_actions('loop_end');
//			remove_all_actions('pre_get_comments');
//			remove_all_actions('wp_meta');
//			remove_all_actions('get_footer');
//			remove_all_actions('wp_footer');
//			remove_all_actions('wp_print_footer_scripts');

			// Don't run the resource manager
			$valid_request = FALSE;
		}

		return $valid_request;
	}

  function _render_related_string()
  {
      $settings = C_NextGen_Settings::get_instance();
      $type = $settings->appendType;
      $maxImages = $settings->maxImages;
      $sluglist = array();

      switch ($type) {
          case 'tags':
              if (function_exists('get_the_tags'))
              {
                  $taglist = get_the_tags();
                  if (is_array($taglist)) {
                      foreach ($taglist as $tag) {
                          $sluglist[] = $tag->slug;
                      }
                  }
              }
              break;
          case 'category':
              $catlist = get_the_category();
              if (is_array($catlist))
              {
                  foreach ($catlist as $cat) {
                      $sluglist[] = $cat->category_nicename;
                  }
              }
              break;
      }

      $taglist = implode(',', $sluglist);

      if ($taglist === 'uncategorized' || empty($taglist))
          return;

      $renderer = C_Component_Registry::get_instance()->get_utility('I_Displayed_Gallery_Renderer');
      $view     = C_Component_Registry::get_instance()->get_utility('I_Component_Factory')
                                                      ->create('mvc_view', '');
      $retval = $renderer->display_images(array(
          'source' => 'tags',
          'container_ids' => $taglist,
          'display_type' => NEXTGEN_GALLERY_BASIC_THUMBNAILS,
          'images_per_page' => $maxImages,
          'maximum_entity_count' => $maxImages,
          'template' => $view->get_template_abspath('photocrati-nextgen_gallery_display#related'),
          'show_all_in_lightbox' => FALSE,
          'show_slideshow_link' => FALSE,
          'disable_pagination' => TRUE,
          'display_no_images_error' => FALSE
      ));

      return apply_filters('ngg_show_related_gallery_content', $retval, $taglist);
  }

	function _render_related_images($content)
	{
    $settings = C_NextGen_Settings::get_instance();
      
		if ($settings->get('activateTags')) {
			$related = $this->_render_related_string();
			
			if ($related != null) {
		    $heading = $settings->relatedHeading;
				$content .= $heading . $related;
			}
		}
		
		return $content;
	}

    /**
     * Adds menu item to the admin bar
     */
    function add_admin_bar_menu()
    {
        global $wp_admin_bar;

        if ( current_user_can('NextGEN Change options') ) {
            $wp_admin_bar->add_menu(array(
                'parent' => 'ngg-menu',
                'id' => 'ngg-menu-display_settings',
                'title' => __('Gallery Settings', 'nggallery'),
                'href' => admin_url('admin.php?page=ngg_display_settings')
            ));
        }
    }

    /**
     * Registers our static settings resources so the ATP module can find them later
     */
    function _register_resources()
    {
		// Register custom post types for compatibility
        $types = array(
			'displayed_gallery'		=>	'NextGEN Gallery - Displayed Gallery',
			'display_type'			=>	'NextGEN Gallery - Display Type',
			'gal_display_source'	=>	'NextGEN Gallery - Displayed Gallery Source'
		);
		foreach ($types as $type => $label) {
			register_post_type($type, array(
				'label'		=>	$label,
				'publicly_queryable'	=>	FALSE,
				'exclude_from_search'	=>	TRUE,
			));
		}

		$router = $this->get_registry()->get_utility('I_Router');

        wp_register_script(
            'nextgen_gallery_display_settings',
            $router->get_static_url('photocrati-nextgen_gallery_display#nextgen_gallery_display_settings.js'),
            array('jquery-ui-accordion', 'jquery-ui-tooltip')
        );

        wp_register_style(
            'nextgen_gallery_display_settings',
            $router->get_static_url('photocrati-nextgen_gallery_display#nextgen_gallery_display_settings.css')
        );

        wp_register_style(
            'nextgen_gallery_related_images',
            $router->get_static_url('photocrati-nextgen_gallery_display#nextgen_gallery_related_images.css')
        );
        wp_enqueue_style('nextgen_gallery_related_images');

        wp_register_script(
            'jquery.nextgen_radio_toggle',
            $router->get_static_url('photocrati-nextgen_gallery_display#jquery.nextgen_radio_toggle.js'),
            array('jquery')
        );

        wp_register_script('ngg_common', $router->get_static_url('photocrati-nextgen_gallery_display#common.js'), array('jquery'));
    }


	/**
	 * Adds the display settings page to wp-admin
	 */
	function add_display_settings_page()
	{
		add_submenu_page(
			NGGFOLDER,
			_('NextGEN Gallery & Album Settings'),
			_('Gallery Settings'),
			'NextGEN Change options',
			NEXTGEN_DISPLAY_SETTINGS_SLUG,
			array(&$this->controller, 'index_action')
		);
	}

	/**
	 * Provides the [display_images] shortcode
	 * @param array $params
	 * @param string $inner_content
	 * @return string
	 */
	function display_images($params, $inner_content=NULL)
	{
		$renderer = $this->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
		return $renderer->display_images($params, $inner_content);
	}

    /**
     * Gets a value from the parameter array, and if not available, uses the default value
     *
     * @param string $name
     * @param mixed $default
     * @param array $params
     * @return mixed
     */
    function _get_param($name, $default, $params)
    {
        return (isset($params[$name])) ? $params[$name] : $default;
    }

    function get_type_list()
    {
        return array(
			'A_Gallery_Display_Ajax'		=>	'adapter.gallery_display_ajax.php',
            'A_Display_Settings_Controller' => 'adapter.display_settings_controller.php',
            'A_Display_Settings_Page' 		=> 'adapter.display_settings_page.php',
            'A_Gallery_Display_Factory' 	=> 'adapter.gallery_display_factory.php',
            'C_Gallery_Display_Installer' 	=> 'class.gallery_display_installer.php',
            'A_Gallery_Display_View' 		=> 'adapter.gallery_display_view.php',
            'C_Displayed_Gallery' 			=> 'class.displayed_gallery.php',
            'C_Displayed_Gallery_Mapper' 	=> 'class.displayed_gallery_mapper.php',
            'C_Displayed_Gallery_Renderer' 	=> 'class.displayed_gallery_renderer.php',
            'C_Displayed_Gallery_Source' 	=> 'class.displayed_gallery_source.php',
            'C_Displayed_Gallery_Source_Mapper' => 'class.displayed_gallery_source_mapper.php',
            'C_Display_Type' 				=> 'class.display_type.php',
            'C_Display_Type_Controller' 	=> 'class.display_type_controller.php',
            'C_Display_Type_Mapper' 		=> 'class.display_type_mapper.php',
            'Hook_Propagate_Thumbnail_Dimensions_To_Settings' => 'hook.propagate_thumbnail_dimensions_to_settings.php',
            'I_Displayed_Gallery' 			=> 'interface.displayed_gallery.php',
            'I_Displayed_Gallery_Mapper' 	=> 'interface.displayed_gallery_mapper.php',
            'I_Displayed_Gallery_Renderer' 	=> 'interface.displayed_gallery_renderer.php',
            'I_Displayed_Gallery_Source' 	=> 'interface.displayed_gallery_source.php',
            'I_Displayed_Gallery_Source_Mapper' => 'interface.displayed_gallery_source_mapper.php',
            'I_Display_Settings_Controller' => 'interface.display_settings_controller.php',
            'I_Display_Type' 				=> 'interface.display_type.php',
            'I_Display_Type_Controller' 	=> 'interface.display_type_controller.php',
            'I_Display_Type_Mapper' 		=> 'interface.display_type_mapper.php',
            'Mixin_Display_Type_Form' 		=> 'mixin.display_type_form.php'
        );
    }
}

new M_Gallery_Display();
