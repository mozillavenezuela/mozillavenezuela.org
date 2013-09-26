<?php

class C_Displayed_Gallery_Renderer extends C_Component
{
    static $_instances = array();

    /**
     * Returns an instance of the class
     * @param mixed $context
     * @return C_Displayed_Gallery_Renderer
     */
    static function get_instance($context=FALSE)
    {
        if (!isset(self::$_instances[$context])) {
            $klass = __CLASS__;
            self::$_instances[$context]= new $klass($context);
        }
        return self::$_instances[$context];
    }


    /**
     * Defines the object
     * @param bool $context
     */
    function define($context=FALSE)
    {
        parent::define($context);
        $this->add_mixin('Mixin_Displayed_Gallery_Renderer');
        $this->implement('I_Displayed_Gallery_Renderer');
    }
}


/**
 * Provides the ability to render a display type
 */
class Mixin_Displayed_Gallery_Renderer extends Mixin
{
    /**
     * Displays a "displayed gallery" instance
     *
     * Alias Properties:
     * gallery_ids/album_ids/tag_ids == container_ids
     * image_ids/gallery_ids		 == entity_ids
     *
     * Default Behavior:
     * - if order_by and order_direction are missing, the default settings
     *   are used from the "Other Options" page. The exception to this is
     *   when entity_ids are selected, in which the order is custom unless
     *   specified.
     *
     * How to use:
     *
     * To retrieve images from gallery 1 & 3, but exclude images 4 & 6:
     * [ngg_images gallery_ids="1,3" exclusions="4,6" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve images 1 & 2 from gallery 1:
     * [ngg_images gallery_ids="1" image_ids="1,2" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve images matching tags "landscapes" and "wedding shoots":
     * [ngg_images tag_ids="landscapes,wedding shoots" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve galleries from albums 1 & #, but exclude sub-album 1:
     * [ngg_images album_ids="1,2" exclusions="a1" display_type="photocrati-nextgen_basic_compact_album"]
     *
     * To retrieve galleries from albums 1 & 2, but exclude gallery 1:
     * [ngg_images album_ids="1,2" exclusions="1" display_type="photocrati-nextgen_basic_compact_album"]
     *
     * To retrieve image 2, 3, and 5 - independent of what container is used
     * [ngg_images image_ids="2,3,5" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve galleries 3 & 5, custom sorted, in album view
     * [ngg_images source="albums" gallery_ids="3,5" display_type="photocrati-nextgen_basic_compact_album"]
     *
     * To retrieve recent images, sorted by alt/title text
     * [ngg_images source="recent" order_by="alttext" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve random image
     * [ngg_images source="random" display_type="photocrati-nextgen_basic_thumbnails"]
     *
     * To retrieve a single image
     * [ngg_images image_ids='8' display_type='photocrati-nextgen_basic_singlepic']
     *
     * To retrieve a tag cloud
     * [ngg_images tagcloud=yes display_type='photocrati-nextgen_basic_tagcloud']
     */
    function display_images($params, $inner_content=NULL, $mode=NULL)
    {
        $retval = '';
        $displayed_gallery = NULL;

        // Get the NextGEN settings to provide some defaults
        $settings = C_NextGen_Settings::get_instance();

        // Configure the arguments
        $defaults = array(
            'id'				=>	NULL,
            'source'			=>	'',
            'container_ids'		=>	array(),
            'gallery_ids'		=>	array(),
            'album_ids'			=>	array(),
            'tag_ids'			=>	array(),
            'display_type'		=>	'',
            'exclusions'		=>	array(),
            'order_by'			=>	$settings->galSort,
            'order_direction'	=>	$settings->galSortOrder,
            'image_ids'			=>	array(),
            'entity_ids'		=>	array(),
            'tagcloud'          => FALSE,
            'inner_content'     => $inner_content,
            'returns'           => 'included',
            'slug'              => NULL
        );
        $args = shortcode_atts($defaults, $params);

        // Are we loading a specific displayed gallery that's persisted?
        $mapper = $this->get_registry()->get_utility('I_Displayed_Gallery_Mapper');
        if (!is_null($args['id'])) {
            $displayed_gallery = $mapper->find($args['id']);
            unset($mapper); // no longer needed
        }

        // We're generating a new displayed gallery
        else {
            // Perform some conversions...

            // Galleries?
            if ($args['gallery_ids'])
            {
                if ($args['source'] != 'albums' AND $args['source'] != 'album')
                {
                    $args['source']        = 'galleries';
                    $args['container_ids'] = $args['gallery_ids'];
                    if ($args['image_ids'])
                        $args['entity_ids'] = $args['image_ids'];
                }
                elseif ($args['source'] == 'albums') {
                    $args['entity_ids']	= $args['gallery_ids'];
                }
                unset($args['gallery_ids']);
            }

            // Albums ?
            elseif ($args['album_ids'] || $args['album_ids'] === '0') {
                $args['source'] = 'albums';
                $args['container_ids'] = $args['album_ids'];
                unset($args['albums_ids']);
            }

            // Tags ?
            elseif ($args['tag_ids']) {
                $args['source'] = 'tags';
                $args['container_ids'] = $args['tag_ids'];
                unset($args['tag_ids']);
            }

            // Specific images selected
            elseif ($args['image_ids']) {
                $args['source'] = 'galleries';
                $args['entity_ids'] = $args['image_ids'];
                unset($args['image_ids']);
            }

            // Tagcloud support
            elseif ($args['tagcloud']) {
                $args['source'] = 'tags';
            }

            // Convert strings to arrays
            if (!is_array($args['container_ids']))
                $args['container_ids'] = preg_split("/,|\|/", $args['container_ids']);

            if (!is_array($args['exclusions']))
                $args['exclusions'] = preg_split("/,|\|/", $args['exclusions']);

            if (!is_array($args['entity_ids']))
                $args['entity_ids'] = preg_split("/,|\|/", $args['entity_ids']);

            // Get the display settings
            foreach (array_keys($defaults) as $key) {
                unset($params[$key]);
            }

            $args['display_settings'] = $params;

            // Create the displayed gallery
            $factory = $this->get_registry()->get_utility('I_Component_Factory');
            $displayed_gallery = $factory->create('displayed_gallery', $mapper, $args);

            unset($factory);
        }

        // Validate the displayed gallery
        if ($displayed_gallery) {
            if ($displayed_gallery->validate()) {

                // Set a temporary id
                $displayed_gallery->id($displayed_gallery->to_transient());


                // Display!
                return $this->object->render($displayed_gallery, TRUE, $mode);
            }
            else $retval =  "Invalid Displayed Gallery".var_dump($displayed_gallery->get_errors());
        }
        else {
            $retval = "Invalid Displayed Gallery";
        }
        return $retval;
    }


    /**
     * Renders a displayed gallery on the frontend
     * @param C_Displayed_Gallery|stdClass $displayed_gallery
     */
    function render($displayed_gallery, $return=FALSE, $mode = null)
    {
        // Simply throwing our rendered gallery into a feed will most likely not work correctly.
        // The MediaRSS option in NextGEN is available as an alternative.
        if(is_feed())
            return '';

    		if ($mode == null)
    		{
    			$mode = 'normal';
    		}

        // Save the displayed gallery as a transient if it hasn't already. Allows for ajax operations
        // to add or modify the gallery without losing a retrievable ID
        if (empty($displayed_gallery->transient_id))
            $displayed_gallery->transient_id = $displayed_gallery->to_transient();

        // Get the display type controller
        $controller = $this->get_registry()->get_utility(
            'I_Display_Type_Controller', $displayed_gallery->display_type
        );

		// Enqueue any necessary static resources
		$controller->enqueue_frontend_resources($displayed_gallery);

		// Get routing info
		$router = $url = C_Router::get_instance();
		$url    = $router->get_url($router->get_request_uri(), TRUE);

		// Should we lookup in cache?
		$lookup = TRUE;
		if ($displayed_gallery->source == 'random_images') $lookup = FALSE;
		elseif (is_array($displayed_gallery->container_ids) && in_array('All', $displayed_gallery->container_ids)) $lookup = FALSE;
		elseif ($displayed_gallery->source == 'albums' && ($controller->param('gallery')) OR $controller->param('album')) $lookup = FALSE;
		elseif (!$controller->cachable) $lookup = FALSE;

		// Try cache lookup, if we're to do so
		$key = null;
		$html = FALSE;
		if ($lookup) {
			// Some settings affect display types
			$settings = C_NextGen_Settings::get_instance();
			$key_params = apply_filters('ngg_displayed_gallery_cache_params', array(
				$displayed_gallery->get_entity(),
				$url,
				$mode,
				$settings->activateTags,
				$settings->appendType,
				$settings->maxImages,
				$settings->thumbEffect,
				$settings->thumbCode,
				$settings->galSort,
				$settings->galSortDir
			));

			// Try getting the rendered HTML from the cache
			$key  = C_Photocrati_Cache::generate_key($key_params);
			$html = C_Photocrati_Cache::get($key, FALSE);

			// TODO: This is hack. We need to figure out a more uniform way of detecting dynamic image urls
			if (strpos($html, C_Photocrati_Settings_Manager::get_instance()->dynamic_thumbnail_slug) !== FALSE) {
				$html = FALSE; // forces the cache to be re-generated
			}
		}

		// If a cached version doesn't exist, then create the cache
		if (!$html) {
			$current_mode = $controller->get_render_mode();
			$controller->set_render_mode($mode);
			$html = $controller->index_action($displayed_gallery, TRUE);
			if ($key != null) 
				C_Photocrati_Cache::set($key, $html);
			$controller->set_render_mode($current_mode);

			// Compress the html to avoid wpautop problems
			$html = $this->compress_html($html);
		}

		if (!$return) echo $html;

		return $html;
    }

	/**
	 * Removes any un-nessessary whitespace from the HTML
	 * @param string $html
	 * @return string
	 */
	function compress_html($html)
	{
		$html = preg_replace("/>\\s+/", ">", $html);
		$html = preg_replace("/\\s+</", "<", $html);
		$html = preg_replace("/<!--(?:(?!-->).)*-->/m", "", $html);
		return $html;
	}
}
