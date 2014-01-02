<?php

class A_NextGen_Basic_Album_Controller extends Mixin
{

    function initialize()
    {
		$this->albums = array();
        $this->object->add_mixin('Mixin_NextGen_Basic_Pagination');
    }

    /**
     * Renders the front-end for the NextGen Basic Album display type
     *
     * @param $displayed_gallery
     * @param bool $return
     */
    function index_action($displayed_gallery, $return = FALSE)
    {
        $display_settings = $displayed_gallery->display_settings;

		// We need to fetch the album containers selected in the Attach
		// to Post interface. We need to do this, because once we fetch the
		// included entities, we need to iterate over each entity and assign it
		// a parent_id, which is the album that it belongs to. We need to do this
		// because the link to the gallery, is not /nggallery/gallery--id, but
		// /nggallery/album--id/gallery--id

		// Are we to display a gallery?
        if (($gallery = $gallery_slug = $this->param('gallery')))
        {
            // basic albums only support one per post
            if (isset($GLOBALS['nggShowGallery']))
                return;
            $GLOBALS['nggShowGallery'] = TRUE;

			// Try finding the gallery by slug first. If nothing is found, we assume that
			// the user passed in a gallery id instead
			$mapper = $this->object->get_registry()->get_utility('I_Gallery_Mapper');
			$result = reset($mapper->select()->where(array('slug = %s', $gallery))->limit(1)->run_query());
			if ($result) {
				$gallery = $result->{$result->id_field};
			}


            $renderer = $this->object->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
            return $renderer->display_images(
                array(
                    'source'				=> 'galleries',
                    'container_ids'			=> array($gallery),
                    'display_type'			=> $display_settings['gallery_display_type'],
					'original_display_type'	=> $displayed_gallery->display_type
                ),
                $return
            );
        }

		// If we're viewing a sub-album, then we use that album as a container instead
		else if (($album = $this->param('album'))) {

			// Are we to display a sub-album?
            {
                $mapper = $this->object->get_registry()->get_utility('I_Album_Mapper');
                $result = array_pop($mapper->select()->where(array('slug = %s', $album))->limit(1)->run_query());
                $album_sub = $result ? $result->{$result->id_field} : null;
                
                if ($album_sub != null) {
                	$album = $album_sub;
                }
            }
            $displayed_gallery->entity_ids = array();
			$displayed_gallery->sortorder = array();
            $displayed_gallery->container_ids = ($album === '0' OR $album === 'all') ? array() : array($album);
		}

		// Get the albums
		$this->albums = $displayed_gallery->get_albums();

        // None of the above: Display the main album. Get the settings required for display
        $current_page = (int)$this->param('page', 1);
        $offset = $display_settings['galleries_per_page'] * ($current_page - 1);
        $entities = $displayed_gallery->get_included_entities($display_settings['galleries_per_page'], $offset);

        // If there are entities to be displayed
        if ($entities)
        {
            if (!empty($display_settings['template']))
            {
                // Add additional parameters
                $pagination_result = $this->object->create_pagination(
                    $current_page,
                    $displayed_gallery->get_entity_count(),
                    $display_settings['galleries_per_page'],
                    urldecode($this->object->param('ajax_pagination_referrer'))
                );
                $this->object->remove_param('ajax_pagination_referrer');
                $display_settings['current_page'] = $current_page;
                $display_settings['entities']     = &$entities;
                $display_settings['pagination_prev'] = $pagination_result['prev'];
                $display_settings['pagination_next'] = $pagination_result['next'];
                $display_settings['pagination']      = $pagination_result['output'];

                // Render legacy template
                $this->object->add_mixin('Mixin_NextGen_Basic_Templates');
                $display_settings = $this->prepare_legacy_album_params($displayed_gallery->get_entity(), $display_settings);
                return $this->object->legacy_render($display_settings['template'], $display_settings, $return, 'album');
            }
            else {
                $params = $display_settings;
                $albums = $this->prepare_legacy_album_params($displayed_gallery->get_entity(), array('entities' => $entities));;
                $params['image_gen_params'] = $albums['image_gen_params'];
                $params['galleries'] = $albums['galleries'];
                $params['displayed_gallery'] = $displayed_gallery;
                $params = $this->object->prepare_display_parameters($displayed_gallery, $params);

                switch ($displayed_gallery->display_type) {
                    case NEXTGEN_GALLERY_NEXTGEN_BASIC_COMPACT_ALBUM:
                        $template = 'compact';
                        break;
                    case NEXTGEN_GALLERY_NEXTGEN_BASIC_EXTENDED_ALBUM:
                        $template = 'extended';
                        break;
                }

                return $this->object->render_view("photocrati-nextgen_basic_album#{$template}", $params, $return);
            }
        }
        else {
            return $this->object->render_partial('photocrati-nextgen_gallery_display#no_images_found', array(), $return);
        }
    }

	/**
	 * Gets the parent album for the entity being displayed
	 * @param int $entity_id
	 * @return stdClass (album)
	 */
	function get_parent_album_for($entity_id)
	{
		$retval = NULL;

		foreach ($this->albums as $album) {
			if (in_array($entity_id, $album->sortorder)) {
				$retval = $album;
				break;
			}
		}

		return $retval;
	}


    function prepare_legacy_album_params($displayed_gallery, $params)
    {
        $image_mapper = $this->object->get_registry()->get_utility('I_Image_Mapper');
        $storage      = $this->object->get_registry()->get_utility('I_Gallery_Storage');
        $image_gen    = $this->object->get_registry()->get_utility('I_Dynamic_Thumbnails_Manager');

        if (empty($displayed_gallery->display_settings['override_thumbnail_settings']))
        {
            // legacy templates expect these dimensions
            $image_gen_params = array(
                'width'  => 91,
                'height' => 68,
                'crop'   => TRUE
            );
        }
        else {
            // use settings requested by user
            $image_gen_params = array(
                'width'     => $displayed_gallery->display_settings['thumbnail_width'],
                'height'    => $displayed_gallery->display_settings['thumbnail_height'],
                'quality'   => $displayed_gallery->display_settings['thumbnail_quality'],
                'crop'      => $displayed_gallery->display_settings['thumbnail_crop'],
                'watermark' => $displayed_gallery->display_settings['thumbnail_watermark']
            );
        }

        // so user templates can know how big the images are expected to be
        $params['image_gen_params'] = $image_gen_params;

        // Transform entities
        $params['galleries'] = $params['entities'];
        unset($params['entities']);

        foreach ($params['galleries'] as &$gallery) {

            // Get the preview image url
            $gallery->previewurl = '';
            if ($gallery->previewpic && $gallery->previewpic > 0)
            {
                if (($image = $image_mapper->find(intval($gallery->previewpic))))
                {
                    $gallery->previewurl = $storage->get_image_url($image, $image_gen->get_size_name($image_gen_params), TRUE);
                    $gallery->previewname = $gallery->name;
                }
            }

            // Get the page link. If the entity is an album, then the url will
			// look like /nggallery/album--slug.
            $id_field = $gallery->id_field;
			if ($gallery->is_album)
            {
                if ($gallery->pageid > 0)
                    $gallery->pagelink = get_post_permalink($gallery->pageid);
                else {
                    $gallery->pagelink = $this->object->set_param_for(
                        $this->object->get_routed_url(TRUE),
                        'album',
                        $gallery->slug
                    );
                }
			}

			// Otherwise, if it's a gallery then it will look like
			// /nggallery/album--slug/gallery--slug
			else {
                if ($gallery->pageid > 0) {
					$gallery->pagelink = @get_post_permalink($gallery->pageid);
				}
                if (empty($gallery->pagelink)) {
                    $pagelink = $this->object->get_routed_url(TRUE);
                    $parent_album = $this->object->get_parent_album_for($gallery->$id_field);
                    if ($parent_album) {
                        $pagelink = $this->object->set_param_for(
                            $pagelink,
                            'album',
                            $parent_album->slug
                        );
                    }
                    // Legacy compat: use an album slug of 'all' if we're missing a container_id
                    else if($displayed_gallery->container_ids === array('0')
                         || $displayed_gallery->container_ids === array('')) {
                        $pagelink = $this->object->set_param_for($pagelink, 'album', 'all');
                    }
                    else {
                        $pagelink = $this->object->set_param_for($pagelink, 'album', 'album');
                    }
                    $gallery->pagelink = $this->object->set_param_for(
                        $pagelink,
                        'gallery',
                        $gallery->slug
                    );
                }
			}

			// The router by default will generate param segments that look like,
			// /gallery--foobar. We need to convert these to the admittingly
			// nicer links that ngglegacy uses
            if ($gallery->pageid <= 0)
                $gallery->pagelink = $this->object->prettify_pagelink($gallery->pagelink);

            // Let plugins modify the gallery
            $gallery = apply_filters('ngg_album_galleryobject', $gallery);
        }

        // Clean up
        unset($storage);
        unset($image_mapper);
        unset($image_gen);
        unset($image_gen_params);

        return $params;
    }


	function prettify_pagelink($pagelink)
	{
		$param_separator = C_NextGen_Settings::get_instance()->get('router_param_separator');

		$regex = implode('', array(
			'#',
			'/(gallery|album)',
			preg_quote($param_separator, '#'),
			'([^/?]+)',
			'#'
		));
		
		$pagelink = preg_replace($regex, '/\2', $pagelink);
		
		return $pagelink;
	}


    function _get_js_lib_url()
    {
        return $this->object->get_static_url('photocrati-nextgen_basic_album#init.js');
    }

    /**
     * Enqueues all static resources required by this display type
     *
     * @param C_Displayed_Gallery $displayed_gallery
     */
    function enqueue_frontend_resources($displayed_gallery)
    {
        $this->call_parent('enqueue_frontend_resources', $displayed_gallery);

        wp_enqueue_style('nextgen_basic_album_style', $this->object->get_static_url('photocrati-nextgen_basic_album#nextgen_basic_album.css'));
        wp_enqueue_script('jquery.dotdotdot', $this->object->get_static_url('photocrati-nextgen_basic_album#jquery.dotdotdot-1.5.7-packed.js'), array('jquery'));

		$this->enqueue_ngg_styles();

    }

}
