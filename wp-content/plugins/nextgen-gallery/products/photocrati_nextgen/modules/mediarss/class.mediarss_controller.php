<?php

class Mixin_MediaRSS_Controller extends Mixin
{
	/**
	 * Renders a MediaRSS feed
	 */
	function index_action()
	{
		$this->object->set_content_type('xml');

		if ($this->object->param('source')) {
			$method = 'render_'.$this->object->param('source');
			if ($this->object->has_method($method)) {
				$this->object->$method();
			}
		}
		else $this->object->http_error("No source specified");
	}

	function render_latest_images()
	{
		$this->object->set_param('params', json_encode(array(
			'source'		=>	'recent'
		)));

		$this->object->render_displayed_gallery();
	}

	/**
	 * Renders a feed for a displayed gallery
	 */
	function render_displayed_gallery()
	{
		$displayed_gallery = NULL;
		$mapper = $this->object->get_registry()->get_utility('I_Displayed_Gallery_Mapper');
		$template = $this->object->param('template');

		if (!in_array($template, array('mediarss_feed', 'playlist_feed'))) {
			$template = 'mediarss_feed';
		}
		
		$template = 'photocrati-mediarss#' . $template;

		// Find the displayed gallery by it's database id
		if (($id = $this->object->param('id'))) {
			$displayed_gallery = $mapper->find($id, TRUE);
		}
        elseif ($transient_id = $this->object->param('transient_id'))
        {
            // retrieve by transient id
            $factory           = $this->object->get_registry()->get_utility('I_Component_Factory');
            $displayed_gallery = $factory->create('displayed_gallery', NULL, $mapper);
            $displayed_gallery->apply_transient($transient_id);
        }
        elseif (($params = $this->object->param('params')))
		{
            // Create the displayed gallery based on the URL parameters
			$factory = $this->object->get_registry()->get_utility('I_Component_Factory');
			$displayed_gallery = $factory->create(
				'displayed_gallery', json_decode($params), $mapper
			);
		}

		// Assuming we have a displayed gallery, display it!
		if ($displayed_gallery) {
			$storage = $this->object->get_registry()->get_utility('I_Gallery_Storage');
			$this->render_view($template, array(
				'storage'			=>	$storage,
				'images'			=>	$displayed_gallery->get_included_entities(),
				'feed_title'		=>	$this->object->_get_feed_title($displayed_gallery),
				'feed_description'	=>	$this->object->_get_feed_description($displayed_gallery),
				'feed_link'			=>	$this->object->_get_feed_link($displayed_gallery),
				'generator'			=>	$this->object->_get_feed_generator($displayed_gallery),
				'copyright'			=>	$this->object->_get_feed_copyright($displayed_gallery),
			));
		}
		else {
			$this->object->http_error("Invalid ID", 404);
		}
	}

	/**
	 * Gets the name of the feed generator
	 * @param C_Displayed_Gallery $displayed_gallery
	 * @return string
	 */
	function _get_feed_generator($displayed_gallery)
	{
		return 'NextGEN Gallery [http://nextgen-gallery.com]';
	}


	/**
	 * Gets the copyright for the feed
	 */
	function _get_feed_copyright($displayed_gallery)
	{
		$site_url = $this->object->get_site_url();
		$blog_name	= get_option('blogname');
		return "Copyright (C) {$blog_name} ({$site_url})";
	}

	/**
	 * Gets the Site URL
	 * @return string
	 */
	function get_site_url()
	{
		$router		= $this->get_registry()->get_utility('I_Router');
		return $router->get_base_url();
	}

	/**
	 * Gets a description for the feed
	 * @param C_Displayed_Gallery $displayed_gallery
	 * @return string
	 */
	function _get_feed_description($displayed_gallery)
	{
		return '';
	}

	/**
	 * Gets a link for the feed
	 * @param C_Displayed_Gallery $displayed_gallery
	 * @return string
	 */
	function _get_feed_link($displayed_gallery)
	{
		return $this->object->get_site_url();
	}


	/**
	 * Gets a title for the feed
	 * @param C_Displayed_Gallery $displayed_gallery
	 * @return string
	 */
	function _get_feed_title($displayed_gallery)
	{
		// Get gallery titles
		$gallery_titles = array();
		foreach ($displayed_gallery->get_galleries() as $gallery) {
			$gallery_titles[] = $gallery->title;
		}

		return "Images from: ".implode(', ', $gallery_titles);
	}
}

class C_MediaRSS_Controller extends C_MVC_Controller
{
	static $_instances = array();

	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_MediaRSS_Controller');
		$this->implement('I_MediaRSS_Controller');
	}

	static function get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}
}

