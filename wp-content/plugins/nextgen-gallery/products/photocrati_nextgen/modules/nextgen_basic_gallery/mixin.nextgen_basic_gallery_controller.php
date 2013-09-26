<?php

class Mixin_NextGen_Basic_Gallery_Controller extends Mixin
{
    function initialize()
    {
        $this->object->add_pre_hook(
            'index_action',
            get_class(),
            'Hook_NextGen_Basic_Gallery_Integration'
        );    
    }
    
    
    /**
     * Returns a url to view the displayed gallery using an alternate display
     * type
     * @param C_Displayed_Gallery $displayed_gallery
     * @param string $display_type
     * @return string
     */
    function get_url_for_alternate_display_type($displayed_gallery, $display_type, $origin_url = FALSE)
    {
        $url = ($origin_url ? $origin_url : $this->object->get_routed_url(TRUE));
        $url = $this->object->remove_param_for($url, 'show', $displayed_gallery->id());
        $url = $this->object->set_param_for($url, 'show', $display_type, $displayed_gallery->id());

        return $url;
    }
}
