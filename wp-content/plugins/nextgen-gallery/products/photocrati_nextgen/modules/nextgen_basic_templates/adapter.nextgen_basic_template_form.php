<?php

class A_NextGen_Basic_Template_Form extends Mixin
{
    /**
     * Renders 'template' settings field
     *
     * @param $display_type
     * @return mixed
     */
    function _render_nextgen_basic_templates_template_field($display_type)
    {
        switch($display_type->name) {
            case 'photocrati-nextgen_basic_singlepic':
                $prefix = 'singlepic';
                break;
            case 'photocrati-nextgen_basic_thumbnails':
                $prefix = 'gallery';
                break;
            case 'photocrati-nextgen_basic_slideshow':
                $prefix = 'gallery';
                break;
            case 'photocrati-nextgen_basic_imagebrowser':
                $prefix = 'imagebrowser';
                break;
            case NEXTGEN_GALLERY_NEXTGEN_BASIC_COMPACT_ALBUM:
                $prefix = 'album';
                break;
            case NEXTGEN_GALLERY_NEXTGEN_BASIC_EXTENDED_ALBUM:
                $prefix = 'album';
                break;
            default:
                $prefix = FALSE;
                break;
        }

        // ensure the current file is in the list
        $templates = $this->object->_get_available_templates($prefix);
        if (!isset($templates[$display_type->settings['template']]))
            $templates[$display_type->settings['template']] = $display_type->settings['template'];

        return $this->object->render_partial(
            'photocrati-nextgen_basic_templates#nextgen_basic_templates_settings_template',
            array(
                'display_type_name' => $display_type->name,
                'template_label'    => _('Template'),
                'template_text'     => _('Use a legacy template when rendering (not recommended).'),
                'chosen_file'       => $display_type->settings['template'],
                'templates'         => $templates
            ),
            True
        );
    }

    /**
     * Retrieves listing of available templates
     *
     * Override this function to modify or add to the available templates listing, array format
     * is array(file_abspath => label)
     * @return array
     */
    function _get_available_templates($prefix = FALSE)
    {
        $templates = array();
        foreach ($this->object
                      ->get_registry()
                      ->get_utility('I_Legacy_Template_Locator')
                      ->find_all($prefix) as $label => $files) {
            foreach ($files as $file) {
                $tmp = explode(DIRECTORY_SEPARATOR, $file);
                $templates[$file] = "{$label}: " . end($tmp);
            }
        }
        asort($templates);
        return $templates;
    }

    /**
     * Returns the parameter objects necessary for legacy template rendering (legacy_render())
     *
     * @param array $images Array of image objects
     * @param string $slideshow_link Slideshow HTML string
     * @param string string $piclens_link Piclens HTML string
     * @param string $pagination Pagination HTML string
     * @return array
     */
    function prepare_legacy_parameters($images, $displayed_gallery, $params = array())
    {
        // setup
		$image_map	  = $this->object->get_registry()->get_utility('I_Image_Mapper');
		$gallery_map  = C_Component_Registry::get_instance()->get_utility('I_Gallery_Mapper');
		$image_key	  = $image_map->get_primary_key_column();
		$gallery_key  = $gallery_map->get_primary_key_column();
        $pid          = $this->object->param('pid');

        // because picture_list implements ArrayAccess any array-specific actions must be taken on
        // $picture_list->container or they won't do anything
        $picture_list = new C_Image_Wrapper_Collection();
        $current_pid  = NULL;

        // begin processing
        $current_page = (@get_the_ID() == FALSE) ? 0 : @get_the_ID();

        // determine what the "current image" is; used mostly for carousel
        if (!is_numeric($pid) && !empty($pid))
        {
            $picture = $image_map->find_first(array('image_slug = %s', $pid));
            $pid = $picture->$image_key;
        }

        // create our new wrappers
        foreach ($images as $image) {
            $new_image = new C_Image_Wrapper($image, $displayed_gallery);
            if ($pid == $new_image->$image_key)
                $current_pid = $new_image;
            $picture_list[] = $new_image;
        }
        reset($picture_list->container);

        // assign current_pid
        $current_pid = (is_null($current_pid)) ? current($picture_list->container) : $current_pid;

        foreach ($picture_list as &$image) {
            if (isset($image->hidden) && $image->hidden)
            {
                $tmp = $displayed_gallery->display_settings['number_of_columns'];
                $image->style = ($tmp > 0) ? 'style="width:' . floor(100 / $tmp) . '%;display: none;"' : 'style="display: none;"';
            }
        }

        // find our gallery to build the new one on
        $orig_gallery = $gallery_map->find(current($picture_list->container)->galleryid);

        // create the 'gallery' object
        $gallery = new stdclass;
        $gallery->ID = $displayed_gallery->id();
        $gallery->name = stripslashes($orig_gallery->name);
        $gallery->title = stripslashes($orig_gallery->title);
        $gallery->description = html_entity_decode(stripslashes($orig_gallery->galdesc));
        $gallery->pageid = $orig_gallery->pageid;

        if ($displayed_gallery->display_settings['ajax_pagination'])
            $gallery_id = $displayed_gallery->transient_id;
        else
            $gallery_id = $displayed_gallery->id();

        $gallery->anchor = 'ngg-gallery-' . $gallery_id . '-' . $current_page;
        $gallery->displayed_gallery = &$displayed_gallery;
        $gallery->columns = @intval($displayed_gallery->display_settings['number_of_columns']);
        $gallery->imagewidth = ($gallery->columns > 0) ? 'style="width:' . floor(100 / $gallery->columns) . '%;"' : '';

        if (!empty($displayed_gallery->display_settings['show_slideshow_link'])) {
            $gallery->show_slideshow = TRUE;
            $gallery->slideshow_link = $params['slideshow_link'];
            $gallery->slideshow_link_text = $displayed_gallery->display_settings['slideshow_link_text'];
        }

        if (!empty($displayed_gallery->display_settings['show_piclens_link'])) {
            $gallery->show_piclens = true;
            $gallery->piclens_link = $params['piclens_link'];
            $gallery->piclens_link_text = $displayed_gallery->display_settings['piclens_link_text'];
        }

        $gallery = apply_filters('ngg_gallery_object', $gallery, 4);

        // build our array of things to return
        $return = array(
            'registry' => C_Component_Registry::get_instance(),
            'gallery'  => $gallery,
        );

        // single_image is an internally added flag
        if (!empty($params['single_image']))
        {
            $return['image'] = $picture_list[0];
        }
        else {
            $return['current'] = $current_pid;
            $return['images']  = $picture_list->container;
        }

        // this is expected to always exist
        if (!empty($params['pagination']))
        {
            $return['pagination'] = $params['pagination'];
        }
        else {
            $return['pagination'] = NULL;
        }

        $return['next'] = $params['next'];
        $return['prev'] = $params['prev'];

        return $return;
    }

	function enqueue_static_resources()
	{
		wp_enqueue_style(
            'ngg_template_settings',
            $this->get_static_url('photocrati-nextgen_basic_templates#ngg_template_settings.css')
        );

        wp_enqueue_script(
            'ngg_template_settings',
            $this->get_static_url('photocrati-nextgen_basic_templates#ngg_template_settings.js'),
            array('jquery-ui-autocomplete', 'jquery-ui-button'),
            $this->module_version,
            TRUE
        );
	}
}
