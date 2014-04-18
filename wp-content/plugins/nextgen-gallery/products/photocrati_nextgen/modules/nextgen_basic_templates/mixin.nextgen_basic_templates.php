<?php

class Mixin_NextGen_Basic_Templates extends Mixin
{
        /**
     * Renders NextGen-Legacy style templates
     *
     * @param string $template_name File name
     * @param array $vars Specially formatted array of parameters
     * @param bool $callback
	 * @param bool $return
     */
    function legacy_render($template_name, $vars = array(), $return = FALSE, $prefix = NULL)
    {
        $retval = "[Not a valid template]";
        $template_locator = $this->object->get_registry()->get_utility('I_Legacy_Template_Locator');

        // search first for files with their prefix
        $template_abspath = $template_locator->find($prefix . '-' . $template_name);
        if (!$template_abspath)
            $template_abspath = $template_locator->find($template_name);

        if ($template_abspath)
        {
            // render the template
            extract($vars);
            if ($return) ob_start();
            include ($template_abspath);
            if ($return) {
                $retval = ob_get_contents();
                ob_end_clean();
            }
        }

        return $retval;
    }
}