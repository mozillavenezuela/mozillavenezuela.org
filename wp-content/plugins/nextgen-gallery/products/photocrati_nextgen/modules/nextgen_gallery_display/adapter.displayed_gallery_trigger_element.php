<?php

class A_Displayed_Gallery_Trigger_Element extends Mixin
{
    function render_object()
    {
        $root_element       = $this->call_parent('render_object');
        if (($displayed_gallery = $this->object->get_param('displayed_gallery')) && $this->object->get_param('display_type_rendering')) {
            $triggers = C_Displayed_Gallery_Trigger_Manager::get_instance();
            $triggers->render($root_element, $displayed_gallery);
        }

        return $root_element;
    }
}