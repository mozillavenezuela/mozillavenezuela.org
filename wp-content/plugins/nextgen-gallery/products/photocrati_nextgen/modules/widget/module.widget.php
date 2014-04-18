<?php
/***
{
        Module: photocrati-widget
}
***/
class M_Widget extends C_Base_Module
{
    /**
     * Defines the module name & version
     */
    function define()
    {
        parent::define(
            'photocrati-widget',
            'Widget',
            'Handles clearing of NextGen Widgets',
            '0.5',
            'http://www.nextgen-gallery.com',
            'Photocrati Media',
            'http://www.photocrati.com'
        );
    }

    /**
     * Register utilities
     */
    function _register_utilities()
    {
        $this->get_registry()->add_utility('I_Widget', 'C_Widget');
    }

    /**
     * Register hooks
     */
    function _register_hooks()
    {
         add_action('widgets_init', create_function('', 'return register_widget("C_Widget_Gallery");'));
         add_action('widgets_init', create_function('', 'return register_widget("C_Widget_MediaRSS");'));
         add_action('widgets_init', create_function('', 'return register_widget("C_Widget_Slideshow");'));
    }

    function get_type_list()
    {
        return array(
            'C_Widget' => 'class.widget.php',
            'C_Widget_Gallery' => 'class.widget_gallery.php',
            'C_Widget_Mediarss' => 'class.widget_mediarss.php',
            'C_Widget_Slideshow' => 'class.widget_slideshow.php',
            'I_Widget' => 'interface.widget.php'
        );
    }

}

new M_Widget();
