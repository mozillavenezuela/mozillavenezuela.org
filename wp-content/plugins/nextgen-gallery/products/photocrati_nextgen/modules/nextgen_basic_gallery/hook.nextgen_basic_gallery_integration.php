<?php

class Hook_NextGen_Basic_Gallery_Integration extends Hook
{
    function index_action($displayed_gallery, $return=FALSE)
    {
        $show = $this->object->param('show');
		$pid  = $this->object->param('pid');

		if (!empty($pid) && isset($displayed_gallery->display_settings['use_imagebrowser_effect']) && intval($displayed_gallery->display_settings['use_imagebrowser_effect']))
			$show = NGG_BASIC_IMAGEBROWSER;

        // Are we to display a different display type?
        if (!empty($show))
        {
            $params = (array)$displayed_gallery->get_entity();
            $ds = $params['display_settings'];

            if ((!empty($ds['show_slideshow_link']) || !empty($ds['show_thumbnail_link']) || !empty($ds['use_imagebrowser_effect']))
            &&   $show != $this->object->context)
            {
                // We've got an alternate request. We'll use a different display
                // type to serve the request and not run the original controller
                // action
                $this->object->set_method_property(
                    $this->method_called,
                    ExtensibleObject::METHOD_PROPERTY_RUN,
                    FALSE
                );
                
                // Render the new display type
                $renderer = $this->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
                $displayed_gallery->original_display_type = $displayed_gallery->display_type;
                $displayed_gallery->display_type = $show;
                $params = (array)$displayed_gallery->get_entity();
                unset($params['display_settings']);
                $retval = $renderer->display_images($params, $return);
                
                // Set return value
                $this->object->set_method_property(
                    $this->method_called,
                    ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE,
                    $retval
                );
                
                return $retval;
            }
        }
    }
}
