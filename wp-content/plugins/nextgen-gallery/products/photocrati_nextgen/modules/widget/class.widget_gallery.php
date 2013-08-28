<?php

class C_Widget_Gallery extends WP_Widget
{
    function __construct()
    {
        $widget_ops = array('classname' => 'ngg_images', 'description' => __('Add recent or random images from the galleries', 'nggallery'));
        $this->WP_Widget('ngg-images', __('NextGEN Widget', 'nggallery'), $widget_ops);
    }

    function form($instance)
    {
        // used for rendering utilities
        $parent = C_Component_Registry::get_instance()->get_utility('I_Widget');

        // defaults
        $instance = wp_parse_args(
            (array)$instance,
            array(
                'exclude'  => 'all',
                'height'   => '75',
                'items'    => '4',
                'list'     =>  '',
                'show'     => 'thumbnail',
                'title'    => 'Gallery',
                'type'     => 'random',
                'webslice' => TRUE,
                'width'    => '100'
            )
        );

        $parent->render_partial(
            'photocrati-widget#form_gallery',
            array(
                'self'     => $this,
                'instance' => $instance,
                'title'    => esc_attr($instance['title']),
                'items'    => intval($instance['items']),
                'height'   => esc_attr($instance['height']),
                'width'    => esc_attr($instance['width'])
            )
        );
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['items'] = (int)$new_instance['items'];
        $instance['type'] = $new_instance['type'];
        $instance['show'] = $new_instance['show'];
        $instance['width'] = (int)$new_instance['width'];
        $instance['height'] = (int)$new_instance['height'];
        $instance['exclude'] = $new_instance['exclude'];
        $instance['list'] = $new_instance['list'];
        $instance['webslice'] = (bool)$new_instance['webslice'];
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

        global $wpdb;

        extract($args);
        $title = apply_filters('widget_title', empty($instance['title']) ? '&nbsp;' : $instance['title'], $instance, $this->id_base);

        $renderer  = C_Component_Registry::get_instance()->get_utility('I_Displayed_Gallery_Renderer');
        $factory   = C_Component_Registry::get_instance()->get_utility('I_Component_Factory');
        $mapper    = C_Component_Registry::get_instance()->get_utility('I_Image_Mapper');
        $view = $factory->create('mvc_view', '');

        // To prevent huge db scans and/or the loading of every image available: we first retrieve X image
        // ids and then create a gallery using the results for the image_ids parameter
        $image_ids = array();

        $sql = "SELECT `pid` FROM `{$wpdb->nggpictures}` WHERE `exclude` = 0";

        // possibly filter images not from certain galleries
        if ($instance['exclude'] == 'allow')
            $sql .= sprintf(" AND `galleryid` IN (%s)", $instance['list']);

        // possibly filter images from certain galleries
        if ($instance['exclude'] == 'denied')
            $sql .= sprintf(" AND `galleryid` NOT IN (%s)", $instance['list']);

        if ($instance['type'] == 'random')
            $sql .= ' ORDER BY RAND()';
        else if ($instance['type'] == 'recent')
            $sql .= ' ORDER BY `imagedate` DESC';

        $sql .= " LIMIT {$instance['items']}";

        foreach ($wpdb->get_results($sql, ARRAY_N) as $res) {
            $image_ids[] = reset($res);
        }
        $image_ids = implode(',', $image_ids);

        if ($instance['type'] == 'random')
        {
            $order_by = 'rand()';
            $order_direction = 'DESC';
        }
        else if ($instance['type'] == 'recent')
        {
            $order_by = $mapper->get_primary_key_column();
            $order_direction = 'DESC';
        }

        // IE8 webslice support if needed
        if ($instance['webslice'])
        {
            $before_widget .= '<div class="hslice" id="ngg-webslice">';
            $before_title  = str_replace('class="' , 'class="entry-title ', $before_title);
            $after_widget  = '</div>' . $after_widget;
        }

        // 'Original' was the value used in 1.9x; so alias original => 'full'
        if ($instance['show'] == 'original')
            $show = 'full';
        else
            $show = 'thumb';

        echo $renderer->display_images(array(
            'source' => 'galleries',
            'order_by' => $order_by,
            'order_direction' => $order_direction,
            'image_ids' => $image_ids,
            'display_type' => NEXTGEN_GALLERY_BASIC_THUMBNAILS,
            'images_per_page' => $instance['items'],
            'maximum_entity_count' => $instance['items'],
            'template' => $view->get_template_abspath('photocrati-widget#display_gallery'),
            'image_type' => $show,
            'show_all_in_lightbox' => FALSE,
            'show_slideshow_link' => FALSE,
            'disable_pagination' => TRUE,
            'image_width' => $instance['width'],
            'image_height' => $instance['height'],
            'ngg_triggers_display' => 'never',
            'widget_setting_title'         => $title,
            'widget_setting_before_widget' => $before_widget,
            'widget_setting_before_title'  => $before_title,
            'widget_setting_after_widget'  => $after_widget,
            'widget_setting_after_title'   => $after_title,
            'widget_setting_width'         => $instance['width'],
            'widget_setting_height'        => $instance['height'],
            'widget_setting_show_setting'  => $instance['show'],
            'widget_setting_widget_id'     => $widget_id
        ));
    }
}
