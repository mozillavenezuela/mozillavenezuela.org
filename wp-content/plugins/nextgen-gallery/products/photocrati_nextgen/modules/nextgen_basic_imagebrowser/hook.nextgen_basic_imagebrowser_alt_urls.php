<?php

class Hook_NextGen_Basic_Imagebrowser_Alt_URLs extends Hook {
    /**
     * Replaces the full-size image url with a path to the current url + a pid (image) parameter. This causes
     * basic thumbnail displays to render a basic imagebrowser.
     *
     * @param $image
     * @param string $size
     * @return null
     */
    function get_image_url($image, $size='full', $check_existance=FALSE)
    {
        // Get the method to be returned
        $retval = $this->object->get_method_property(
            $this->method_called,
            ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE
        );

        if ($size == 'full')
        {
            $router      = $this->object->get_registry()->get_utility('I_Router');
            $controller  = $this->object->get_registry()->get_utility('I_Display_Type_Controller');
            $application = $router->get_routed_app();

            $url = $application->get_routed_url(TRUE);

            $url = $controller->set_param_for($url, 'pid', $image->image_slug);
            $url = $controller->remove_param_for($url, 'show');

            $retval = $url;

            $this->object->set_method_property(
                $this->method_called,
                ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE,
                $retval
            );
        }

        return $retval;
    }
}
