<?php

class A_Ajax_Pagination_Actions extends Mixin
{
    function get_displayed_gallery_page_action()
    {
        $retval = array();
        $mapper = $this->object->get_registry()->get_utility('I_Displayed_Gallery_Mapper');

        if (($id = $this->object->param('displayed_gallery_id')))
        {
            // retrieve by transient id
            $factory           = $this->object->get_registry()->get_utility('I_Component_Factory');
            $displayed_gallery = $factory->create('displayed_gallery', $mapper);
            $displayed_gallery->apply_transient($id);
            $displayed_gallery->transient_id = $id;

            // Prevent future "show slideshow" links from using the AJAX request URI
            if ($displayed_gallery->display_settings['ajax_pagination'])
                $displayed_gallery->display_settings['slideshow_link_origin'] = $_SERVER['HTTP_REFERER'];

            // render the displayed gallery
            $this->renderer                 = $this->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
            $retval['html']                 = $this->renderer->render($displayed_gallery, TRUE);
            $retval['displayed_gallery_id'] = $displayed_gallery->id();
        }
        return $retval;
    }
}