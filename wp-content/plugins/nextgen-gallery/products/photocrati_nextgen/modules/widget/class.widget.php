<?php

class C_Widget extends C_MVC_Controller
{
    public static $_instances = array();

    function define($context = FALSE)
    {
        parent::define($context);
        $this->add_mixin('Mixin_Widget');
        $this->implement('I_Widget');
    }

    public static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context]))
        {
            self::$_instances[$context] = new C_Widget($context);
        }
        return self::$_instances[$context];
    }
}

class Mixin_Widget extends Mixin
{
    /**
     * Function for templates without widget support
     *
     * @return echo the widget content
     */
    function echo_widget_random($number, $width = '75', $height = '50', $exclude = 'all', $list = '', $show = 'thumbnail')
    {
        wp_enqueue_style('nextgen_widgets_style', $this->get_static_url('photocrati-widget#widgets.css'));
        wp_enqueue_style('nextgen_basic_thumbnails_style', $this->get_static_url('nextgen_basic_thumbnails#nextgen_basic_thumbnails.css'));
        $options = array(
            'title'    => FALSE,
            'items'    => $number,
            'show'     => $show ,
            'type'     => 'random',
            'width'    => $width,
            'height'   => $height,
            'exclude'  => $exclude,
            'list'     => $list,
            'webslice' => FALSE
        );
        $widget = new C_Widget_Gallery();
        $widget->widget($args = array('widget_id' => 'sidebar_1'), $options);
    }

    /**
     * Function for templates without widget support
     *
     * @return echo the widget content
     */
    function echo_widget_recent($number, $width = '75', $height = '50', $exclude = 'all', $list = '', $show = 'thumbnail')
    {
        wp_enqueue_style('nextgen_widgets_style', $this->get_static_url('photocrati-widget#widgets.css'));
        wp_enqueue_style('nextgen_basic_thumbnails_style', $this->get_static_url('nextgen_basic_thumbnails#nextgen_basic_thumbnails.css'));
        $options = array(
            'title'    => FALSE,
            'items'    => $number,
            'show'     => $show ,
            'type'     => 'recent',
            'width'    => $width,
            'height'   => $height,
            'exclude'  => $exclude,
            'list'     => $list,
            'webslice' => FALSE
        );
        $widget = new C_Widget_Gallery();
        $widget->widget($args = array('widget_id' => 'sidebar_1'), $options);
    }

    /**
     * Function for templates without widget support
     *
     * @param integer $galleryID
     * @param string $width
     * @param string $height
     * @return echo the widget content
     */
    function echo_widget_slideshow($galleryID, $width = '', $height = '')
    {
        wp_enqueue_style('nextgen_widgets_style', $this->get_static_url('widgets.css'));
        wp_enqueue_style('nextgen_basic_slideshow_style', $this->get_static_url('nextgen_basic_slideshow#nextgen_basic_slideshow.css'));
        $widget = new C_Widget_Slideshow();
        $widget->render_slideshow($galleryID, $width, $height);
    }
}
