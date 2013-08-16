<?php

class C_Widget_Slideshow extends WP_Widget
{
    function __construct()
    {
        $widget_ops = array('classname' => 'widget_slideshow', 'description' => __('Show a NextGEN Gallery Slideshow', 'nggallery'));
        $this->WP_Widget('slideshow', __('NextGEN Slideshow', 'nggallery'), $widget_ops);
    }

    function form($instance)
    {
        global $wpdb;

        // used for rendering utilities
        $parent = C_Component_Registry::get_instance()->get_utility('I_Widget');

        // defaults
        $instance = wp_parse_args(
            (array)$instance,
            array(
                'galleryid' => '0',
                'height' => '120',
                'title' => 'Slideshow',
                'width' => '160'
            )
        );

        $parent->render_partial(
            'photocrati-widget#form_slideshow',
            array(
                'self'     => $this,
                'instance' => $instance,
                'title'    => esc_attr($instance['title']),
                'height'   => esc_attr($instance['height']),
                'width'    => esc_attr($instance['width']),
                'tables'   => $wpdb->get_results("SELECT * FROM {$wpdb->nggallery} ORDER BY 'name' ASC")
            )
        );
    }

    function update($new_instance, $old_instance)
    {
        $nh = $new_instance['height'];
        $nw = $new_instance['width'];
        if (empty($nh) || (int)$nh === 0)
            $new_instance['height'] = 120;
        if (empty($nw) || (int)$nw === 0)
            $new_instance['width'] = 160;

        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['galleryid'] = (int) $new_instance['galleryid'];
        $instance['height'] = (int) $new_instance['height'];
        $instance['width'] = (int) $new_instance['width'];
        return $instance;
    }

    function widget($args, $instance)
    {
        // these are handled by extract() but I want to silence my IDE warnings that these vars don't exist
        $before_widget = NULL;
        $before_title = NULL;
        $after_widget = NULL;
        $after_title = NULL;
        $widget_id = NULL;

        extract($args);

        $parent = C_Component_Registry::get_instance()->get_utility('I_Widget');

        $title = apply_filters('widget_title', empty($instance['title']) ? __('Slideshow', 'nggallery') : $instance['title'], $instance, $this->id_base);

        $out = $this->render_slideshow($instance['galleryid'], $instance['width'], $instance['height']);

        $parent->render_partial(
            'photocrati-widget#display_slideshow',
            array(
                'self'       => $this,
                'instance'   => $instance,
                'title'      => $title,
                'out'        => $out,
                'before_widget' => $before_widget,
                'before_title'  => $before_title,
                'after_widget'  => $after_widget,
                'after_title'   => $after_title,
                'widget_id'     => $widget_id
            )
        );
    }

    function render_slideshow($galleryID, $irWidth = '', $irHeight = '')
    {
        $registry = C_Component_Registry::get_instance();
        $renderer = $registry->get_utility('I_Displayed_Gallery_Renderer');

        $params = array(
            'container_ids'  => $galleryID,
            'display_type'   => 'photocrati-nextgen_basic_slideshow',
            'gallery_width'  => $irWidth,
            'gallery_height' => $irHeight,
            'source'         => 'galleries',
            'entity_types'   => array('image'),
            'show_thumbnail_link' => FALSE,
            'ngg_triggers_display' => 'never'
        );

        if (0 === $galleryID)
        {
            $params['source'] = 'random_images';
            unset($params['container_ids']);
        }

        $retval = $renderer->display_images($params, NULL);
        $retval = apply_filters('ngg_show_slideshow_widget_content', $retval, $galleryID, $irWidth, $irHeight);
        return $retval;
    }

}
