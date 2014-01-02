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

        // do not allow 0 or less
        if ((int)$new_instance['items'] <= 0)
            $new_instance['items'] = 4;

        // for clarity: empty the list if we're showing every gallery anyway
        if ($new_instance['exclude'] == 'all')
            $new_instance['list'] = '';

        // remove gallery ids that do not exist
        if (in_array($new_instance['exclude'], array('denied', 'allow')))
        {
            // do search
            $mapper = C_Component_Registry::get_instance()->get_utility('I_Gallery_Mapper');
            $ids = explode(',', $new_instance['list']);
            foreach ($ids as $ndx => $id) {
                if (!$mapper->find($id))
                    unset($ids[$ndx]);
            }
            $new_instance['list'] = implode(',', $ids);
        }

        // reset to show all galleries IF there are no valid galleries in the list
        if ($new_instance['exclude'] !== 'all' && empty($new_instance['list']))
            $new_instance['exclude'] = 'all';

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
		$router = C_Router::get_instance();
		wp_enqueue_style('nextgen_widgets_style', $router->get_static_url('photocrati-widget#widgets.css'));
		wp_enqueue_style('nextgen_basic_thumbnails_style', $router->get_static_url('photocrati-nextgen_basic_gallery#thumbnails/nextgen_basic_thumbnails.css'));

        // these are handled by extract() but I want to silence my IDE warnings that these vars don't exist
        $before_widget = NULL;
        $before_title  = NULL;
        $after_widget  = NULL;
        $after_title   = NULL;
        $widget_id     = NULL;
        extract($args);

        $title = apply_filters('widget_title', empty($instance['title']) ? '&nbsp;' : $instance['title'], $instance, $this->id_base);

        $renderer = C_Component_Registry::get_instance()->get_utility('I_Displayed_Gallery_Renderer');
        $factory  = C_Component_Registry::get_instance()->get_utility('I_Component_Factory');
        $view = $factory->create('mvc_view', '');

        // IE8 webslice support if needed
        if ($instance['webslice'])
        {
            $before_widget .= '<div class="hslice" id="ngg-webslice">';
            $before_title  = str_replace('class="' , 'class="entry-title ', $before_title);
            $after_widget  = '</div>' . $after_widget;
        }

        $source = ($instance['type'] == 'random' ? 'random_images' : 'recent');

        $params = array(
            'slug' => 'widget-' . $args['widget_id'],
            'source' => $source,
            'display_type' => NEXTGEN_GALLERY_BASIC_THUMBNAILS,
            'images_per_page' => $instance['items'],
            'maximum_entity_count' => $instance['items'],
            'template' => $view->get_template_abspath('photocrati-widget#display_gallery'),
            'image_type' => $instance['show'] == 'original' ? 'full' : 'thumb',
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
        );

        switch ($instance['exclude']) {
            case 'all':
                break;
            case 'denied':
                $mapper = C_Component_Registry::get_instance()->get_utility('I_Gallery_Mapper');
                $gallery_ids = array();
                $list = explode(',', $instance['list']);
                foreach ($mapper->find_all() as $gallery) {
                    if (!in_array($gallery->{$gallery->id_field}, $list))
                        $gallery_ids[] = $gallery->{$gallery->id_field};
                }
                $params['container_ids'] = implode(',', $gallery_ids);
                break;
            case 'allow':
                $params['container_ids'] = $instance['list'];
                break;
        }

        echo $renderer->display_images($params);
    }
}
