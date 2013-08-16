<?php

class A_NextGen_Basic_Singlepic_Controller extends Mixin
{
    /**
     * Displays the 'singlepic' display type
     *
     * @param stdClass|C_Displayed_Gallery|C_DataMapper_Model $displayed_gallery
     */
    function index_action($displayed_gallery, $return = FALSE)
    {
        $storage   = $this->object->get_registry()->get_utility('I_Gallery_Storage');
        $dynthumbs = $this->object->get_registry()->get_utility('I_Dynamic_Thumbnails_Manager');
        $display_settings = $displayed_gallery->display_settings;
		$image = array_shift($displayed_gallery->get_included_entities(1));

        if (!$image)
            return $this->object->render_partial("photocrati-nextgen_gallery_display#no_images_found", array(), $return);

        switch ($display_settings['float']) {
            case 'left':
                $display_settings['float'] = 'ngg-left';
                break;
            case 'right':
                $display_settings['float'] = 'ngg-right';
                break;
            case 'center':
                $display_settings['float'] = 'ngg-center';
                break;
            default:
                $display_settings['float'] = '';
                break;
        }

        // validate and/or clean our passed settings
        $display_settings['link'] = (!empty($display_settings['link'])) ? $display_settings['link'] : $storage->get_image_url($image);

        // mode is a legacy parameter
        if (!is_array($display_settings['mode']))
            $display_settings['mode'] = explode(',', $display_settings['mode']);
        if (in_array('web20', $display_settings['mode']))
            $display_settings['display_reflection'] = TRUE;
        if (in_array('watermark', $display_settings['mode']))
            $display_settings['display_watermark'] = TRUE;
        
	      if (isset($display_settings['w']))
	          $display_settings['width'] = $display_settings['w'];
	      elseif (isset($display_settings['h']))
	      		unset($display_settings['width']);
	          
	      if (isset($display_settings['h']))
	          $display_settings['height'] = $display_settings['h'];
	      elseif (isset($display_settings['w']))
	      		unset($display_settings['height']);
        
        // legacy assumed no width/height meant full size unlike generate_thumbnail: force a full resolution
        if (!isset($display_settings['width']) && !isset($display_settings['height']))
            $display_settings['width'] = $image->meta_data['width'];
        
        if (isset($display_settings['width']))
        		$params['width'] = $display_settings['width'];
        
        if (isset($display_settings['height']))
            $params['height'] = $display_settings['height'];
            
        $params['quality'] = $display_settings['quality'];
        $params['crop'] = $display_settings['crop'];
        $params['watermark'] = $display_settings['display_watermark'];
        $params['reflection'] = $display_settings['display_reflection'];

        // Fall back to full in case dynamic images aren't available
        $size = 'full';

        if ($dynthumbs != null)
            $size = $dynthumbs->get_size_name($params);

        $thumbnail_url = $storage->get_image_url($image, $size);

        if (!empty($display_settings['template']))
        {
            $this->object->add_mixin('A_NextGen_Basic_Template_Form');
            $this->object->add_mixin('Mixin_NextGen_Basic_Templates');
            $params = $this->object->prepare_legacy_parameters(array($image), $displayed_gallery, array('single_image' => TRUE));

            // the wrapper is a lazy-loader that calculates variables when requested. We here override those to always
            // return the same precalculated settings provided
            $params['image']->container[0]->_cache_overrides['caption']      = $displayed_gallery->inner_content;
            $params['image']->container[0]->_cache_overrides['classname']    = 'ngg-singlepic ' . $display_settings['float'];
            $params['image']->container[0]->_cache_overrides['imageURL']     = $display_settings['link'];
            $params['image']->container[0]->_cache_overrides['thumbnailURL'] = $thumbnail_url;

            return $this->object->legacy_render($display_settings['template'], $params, $return, 'singlepic');
        }
        else {
            $params = $display_settings;
            $params['storage']       = &$storage;
            $params['image']         = &$image;
            $params['effect_code']   = $this->object->get_effect_code($displayed_gallery);
            $params['inner_content'] = $displayed_gallery->inner_content;
            $params['settings']      = $display_settings;
            $params['thumbnail_url'] = $thumbnail_url;
                
            $params = $this->object->prepare_display_parameters($displayed_gallery, $params);

            return $this->object->render_partial('photocrati-nextgen_basic_singlepic#nextgen_basic_singlepic', $params, $return);
        }
    }

    /**
     * Enqueues all static resources required by this display type
     *
     * @param C_Displayed_Gallery $displayed_gallery
     */
    function enqueue_frontend_resources($displayed_gallery)
    {
		$this->call_parent('enqueue_frontend_resources', $displayed_gallery);

        wp_enqueue_style('nextgen_basic_singlepic_style', $this->get_static_url('photocrati-nextgen_basic_singlepic#nextgen_basic_singlepic.css'));

		$this->enqueue_ngg_styles();
    }

}
