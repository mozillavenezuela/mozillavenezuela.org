<?php

class A_Displayed_Gallery_Related_Element extends Mixin
{
    function initialize()
    {
        $this->object->add_post_hook(
            'render_object',
            'Renders "related" link for the gallery',
            get_class(),
            'render_related'
        );
    }

    function _check_rendering($displayed_gallery, $template_id, $root_element)
    {
    	$ret = $this->object->_check_addition_rendering($displayed_gallery, $template_id, $root_element, 'layout');
    	
    	switch ($template_id)
    	{
    		case 'photocrati-nextgen_basic_album#compact':
    		case 'photocrati-nextgen_basic_album#extended':
    		case 'photocrati-nextgen_basic_tagcloud#nextgen_basic_tagcloud':
    		{
    			$ret = false;
    			
    			break;
    		}
    	}
    	
    	return $ret;
    }

    function render_related()
    {
		if (!C_NextGen_Settings::get_instance()->get('activateTags')) return;

        $root_element = $this->object->get_method_property(
            $this->method_called,
            ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE
        );

        $displayed_type = $this->object->get_param('display_type_rendering');
        $displayed_gallery = $this->object->get_param('displayed_gallery');
        $template_id = $root_element->get_id();

        if (!$this->object->_check_rendering($displayed_gallery, $template_id, $root_element))
            return;

        if ($displayed_type && $displayed_gallery != null)
        {
            $list = $root_element->find('nextgen_gallery.gallery_container', TRUE);
            foreach ($list as $container_element) {
                $container_element->append($this->object->render_related_string());
            }
        }

        return $root_element;
    }

    function render_related_string()
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
            'disable_pagination' => TRUE
        ));

        return apply_filters('ngg_show_related_gallery_content', $retval, $taglist);
    }

}
