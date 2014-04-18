<?php

abstract class C_Displayed_Gallery_Trigger
{
    static function is_renderable($name, $displayed_gallery)
    {
        return TRUE;
    }

    function get_css_class()
    {
        return 'fa fa-circle';
    }

    function get_attributes()
    {
        return array('class' => $this->get_css_class() );
    }

    function render()
    {
        $attributes = array();
        foreach ($this->get_attributes() as $k=>$v) {
            $k = esc_attr($k);
            $v = esc_attr($v);
            $attributes[] = "{$k}='{$v}'";
        }
        $attributes = implode(" ", $attributes);

        return "<i {$attributes}></i>";
    }
}