<?php

class C_Widget_MediaRSS extends WP_Widget
{
    var $options;

    function __construct()
    {
        $widget_ops = array('classname' => 'ngg_mrssw', 'description' => __('Widget that displays Media RSS links for NextGEN Gallery.', 'nggallery'));
        $this->WP_Widget('ngg-mrssw', __('NextGEN Media RSS', 'nggallery'), $widget_ops);
    }

    function form($instance)
    {
        // used for rendering utilities
        $parent = C_Component_Registry::get_instance()->get_utility('I_Widget');

        // defaults
        $instance = wp_parse_args(
            (array)$instance,
            array(
                'mrss_text' => __('Media RSS', 'nggallery'),
                'mrss_title' => __('Link to the main image feed', 'nggallery'),
                'show_global_mrss' => TRUE,
                'show_icon' => TRUE,
                'title' => 'Media RSS'
            )
        );

        $parent->render_partial(
            'photocrati-widget#form_mediarss',
            array(
                'self'       => $this,
                'instance'   => $instance,
                'title'      => esc_attr($instance['title']),
                'mrss_text'  => esc_attr($instance['mrss_text']),
                'mrss_title' => esc_attr($instance['mrss_title'])
            )
        );
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['show_global_mrss'] = $new_instance['show_global_mrss'];
        $instance['show_icon'] = $new_instance['show_icon'];
        $instance['mrss_text'] = $new_instance['mrss_text'];
        $instance['mrss_title'] = $new_instance['mrss_title'];
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

        $settings = C_NextGen_Settings::get_instance();
        $parent = C_Component_Registry::get_instance()->get_utility('I_Widget');

        $title = apply_filters('widget_title', empty($instance['title']) ? '&nbsp;' : $instance['title'], $instance, $this->id_base);

        $parent->render_partial(
            'photocrati-widget#display_mediarss',
            array(
                'self'       => $this,
                'instance'   => $instance,
                'title'      => $title,
                'settings'   => $settings,
                'before_widget' => $before_widget,
                'before_title'  => $before_title,
                'after_widget'  => $after_widget,
                'after_title'   => $after_title,
                'widget_id'     => $widget_id
            )
        );
    }

    function get_mrss_link($mrss_url, $show_icon = TRUE, $title, $text, $use_piclens)
    {
        $out  = '';

        if ($show_icon)
        {
            $icon_url = NGGALLERY_URLPATH . 'images/mrss-icon.gif';
            $out .= "<a href='{$mrss_url}' title='{$title}' class='ngg-media-rss-link'" . ($use_piclens ? ' onclick="PicLensLite.start({feedUrl:\'' . $mrss_url . '\'}); return false;"' : "") . " >";
            $out .= "<img src='{$icon_url}' alt='MediaRSS Icon' title='" . (!$use_piclens ? $title : __('[View with PicLens]','nggallery')). "' class='ngg-media-rss-icon' />";
            $out .=  "</a> ";
        }

        if ($text != '')
        {
            $out .= "<a href='{$mrss_url}' title='{$title}' class='ngg-media-rss-link'>";
            $out .= $text;
            $out .=  "</a>";
        }

        return $out;
    }

}
