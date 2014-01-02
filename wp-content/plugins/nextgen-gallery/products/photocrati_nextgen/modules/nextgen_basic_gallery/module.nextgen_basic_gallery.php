<?php
/*
{
    Module: photocrati-nextgen_basic_gallery,
    Depends: { photocrati-nextgen_pagination }
}
*/

define(
    'NEXTGEN_GALLERY_BASIC_THUMBNAILS',
    'photocrati-nextgen_basic_thumbnails'
);

define(
    'NEXTGEN_GALLERY_BASIC_SLIDESHOW',
    'photocrati-nextgen_basic_slideshow'
);


class M_NextGen_Basic_Gallery extends C_Base_Module
{
    function define()
    {
        parent::define(
            'photocrati-nextgen_basic_gallery',
            'NextGEN Basic Gallery',
            "Provides NextGEN Gallery's basic thumbnail/slideshow integrated gallery",
            '0.7',
            'http://www.nextgen-gallery.com',
            'Photocrati Media',
            'http://www.photocrati.com'
        );

		include_once('class.nextgen_basic_gallery_installer.php');
		C_Photocrati_Installer::add_handler($this->module_id, 'C_NextGen_Basic_Gallery_Installer');
    }

    function get_type_list()
    {
        return array(
            'A_Ajax_Pagination_Actions' => 'adapter.ajax_pagination_actions.php',
            'A_Nextgen_Basic_Gallery_Forms' => 'adapter.nextgen_basic_gallery_forms.php',
            'C_Nextgen_Basic_Gallery_Installer' => 'class.nextgen_basic_gallery_installer.php',
            'A_Nextgen_Basic_Gallery_Mapper' => 'adapter.nextgen_basic_gallery_mapper.php',
            'A_Nextgen_Basic_Gallery_Routes' => 'adapter.nextgen_basic_gallery_routes.php',
            'A_Nextgen_Basic_Gallery_Urls' => 'adapter.nextgen_basic_gallery_urls.php',
            'A_Nextgen_Basic_Gallery_Validation' => 'adapter.nextgen_basic_gallery_validation.php',
            'A_Nextgen_Basic_Slideshow_Controller' => 'adapter.nextgen_basic_slideshow_controller.php',
            'A_Nextgen_Basic_Slideshow_Form' => 'adapter.nextgen_basic_slideshow_form.php',
            'A_Nextgen_Basic_Thumbnail_Form' => 'adapter.nextgen_basic_thumbnail_form.php',
            'A_Nextgen_Basic_Thumbnails_Controller' => 'adapter.nextgen_basic_thumbnails_controller.php',
            'Hook_Nextgen_Basic_Gallery_Integration' => 'hook.nextgen_basic_gallery_integration.php',
            'Mixin_Nextgen_Basic_Gallery_Controller' => 'mixin.nextgen_basic_gallery_controller.php'
        );
    }
    
   
    function _register_adapters()
    {
        if (is_admin()) {
            // Provides the display type forms
            $this->get_registry()->add_adapter(
                'I_Form',
                'A_NextGen_Basic_Slideshow_Form',
                NEXTGEN_GALLERY_BASIC_SLIDESHOW
            );
            $this->get_registry()->add_adapter(
                'I_Form',
                'A_NextGen_Basic_Thumbnail_Form',
                NEXTGEN_GALLERY_BASIC_THUMBNAILS
            );
        }
        
        // Provides the controllers for the display types
        $this->get_registry()->add_adapter(
            'I_Display_Type_Controller',
            'A_NextGen_Basic_Slideshow_Controller',
            NEXTGEN_GALLERY_BASIC_SLIDESHOW
        );
        $this->get_registry()->add_adapter(
            'I_Display_Type_Controller',
            'A_NextGen_Basic_Thumbnails_Controller',
            NEXTGEN_GALLERY_BASIC_THUMBNAILS
        );
        
        // Provide defaults for the display types
        $this->get_registry()->add_adapter(
            'I_Display_Type_Mapper',
            'A_NextGen_Basic_Gallery_Mapper'
        );
        
        // Provides validation for the display types
        $this->get_registry()->add_adapter(
            'I_Display_Type',
            'A_NextGen_Basic_Gallery_Validation'
        );
        
        // Provides url generation support for the display types
        $this->get_registry()->add_adapter(
			'I_Routing_App',
			'A_NextGen_Basic_Gallery_Urls'
		);
        
        // Provides routing logic for the display types
        $this->get_registry()->add_adapter(
            'I_Router',
            'A_NextGen_Basic_Gallery_Routes'
        );
        
        
        // Provides AJAX pagination actions required by the display types
        $this->get_registry()->add_adapter(
            'I_Ajax_Controller',
            'A_Ajax_Pagination_Actions'
        );

        if (is_admin()) {
            // Adds the settings forms
            $this->get_registry()->add_adapter(
                'I_Form_Manager',
                'A_NextGen_Basic_Gallery_Forms'
            );
        }
    }
    
    function _register_hooks()
	{
		C_NextGen_Shortcode_Manager::add('nggallery', array(&$this, 'render'));
		C_NextGen_Shortcode_Manager::add('nggtags',   array(&$this, 'render_based_on_tags'));
		C_NextGen_Shortcode_Manager::add('random',    array(&$this, 'render_random_images'));
		C_NextGen_Shortcode_Manager::add('recent',    array(&$this, 'render_recent_images'));
		C_NextGen_Shortcode_Manager::add('thumb',	   array(&$this, 'render_thumb_shortcode'));
		C_NextGen_Shortcode_Manager::add('slideshow',		 array(&$this, 'render_slideshow'));
		C_NextGen_Shortcode_Manager::add('nggslideshow',	 array(&$this, 'render_slideshow'));
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

	/**
     * Short-cut for rendering an thumbnail gallery
     * @param array $params
     * @param null $inner_content
     * @return string
     */
	function render($params, $inner_content=NULL)
    {
        $params['gallery_ids']     = $this->_get_param('id', NULL, $params);
        $params['display_type']    = $this->_get_param('display_type', NEXTGEN_GALLERY_BASIC_THUMBNAILS, $params);
        if (isset($params['images']))
        {
            $params['images_per_page'] = $this->_get_param('images', NULL, $params);
        }
        unset($params['id']);
        unset($params['images']);

		$renderer = $this->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
        return $renderer->display_images($params, $inner_content);
    }

	function render_based_on_tags($params, $inner_content=NULL)
    {
        $params['tag_ids']      = $this->_get_param('gallery', $this->_get_param('album', array(), $params), $params);
        $params['source']       = $this->_get_param('source', 'tags', $params);
        $params['display_type'] = $this->_get_param('display_type', NEXTGEN_GALLERY_BASIC_THUMBNAILS, $params);
        unset($params['gallery']);

		$renderer = $this->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
        return $renderer->display_images($params, $inner_content);
    }

	function render_random_images($params, $inner_content=NULL)
	{
		$params['source']             = $this->_get_param('source', 'random', $params);
        $params['images_per_page']    = $this->_get_param('max', NULL, $params);
        $params['disable_pagination'] = $this->_get_param('disable_pagination', TRUE, $params);
        $params['display_type']       = $this->_get_param('display_type', NEXTGEN_GALLERY_BASIC_THUMBNAILS, $params);

        // inside if because Mixin_Displayed_Gallery_Instance_Methods->get_entities() doesn't handle NULL container_ids
        // correctly
        if (isset($params['id']))
        {
            $params['container_ids'] = $this->_get_param('id', NULL, $params);
        }

        unset($params['max']);
        unset($params['id']);

		$renderer = $this->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
        return $renderer->display_images($params, $inner_content);
	}

	function render_recent_images($params, $inner_content=NULL)
	{
		        $params['source']             = $this->_get_param('source', 'recent', $params);
        $params['images_per_page']    = $this->_get_param('max', NULL, $params);
        $params['disable_pagination'] = $this->_get_param('disable_pagination', TRUE, $params);
        $params['display_type']       = $this->_get_param('display_type', NEXTGEN_GALLERY_BASIC_THUMBNAILS, $params);

        if (isset($params['id']))
        {
            $params['container_ids'] = $this->_get_param('id', NULL, $params);
        }

        unset($params['max']);
        unset($params['id']);

		$renderer = $this->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
        return $renderer->display_images($params, $inner_content);
	}

	function render_thumb_shortcode($params, $inner_content=NULL)
	{
		$params['entity_ids']   = $this->_get_param('id', NULL, $params);
        $params['source']       = $this->_get_param('source', 'galleries', $params);
        $params['display_type'] = $this->_get_param('display_type', NEXTGEN_GALLERY_BASIC_THUMBNAILS, $params);
        unset($params['id']);

        $renderer = $this->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
        return $renderer->display_images($params, $inner_content);
	}
    
	function render_slideshow($params, $inner_content=NULL)
	{
		$params['gallery_ids']    = $this->_get_param('id', NULL, $params);
        $params['display_type']   = $this->_get_param('display_type', NEXTGEN_GALLERY_BASIC_SLIDESHOW, $params);
        $params['gallery_width']  = $this->_get_param('w', NULL, $params);
        $params['gallery_height'] = $this->_get_param('h', NULL, $params);
        unset($params['id'], $params['w'], $params['h']);

		$renderer = $this->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
        return $renderer->display_images($params, $inner_content);
	}    
}

new M_NextGen_Basic_Gallery;
