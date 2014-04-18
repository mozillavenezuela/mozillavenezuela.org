<?php

/**
 * The Trigger Manager displays "trigger buttons" for a displayed gallery.
 *
 * Each display type can register a "handler", which is a class with a render method, which is used
 * to render the display of the trigger buttons.
 *
 * Each trigger button is registered with a handler, which is also a class with a render() method.
 * Class C_Displayed_Gallery_Trigger_Manager
 */
class C_Displayed_Gallery_Trigger_Manager
{
    static  $_instance = NULL;
    private $_triggers = array();
    private $_trigger_order = array();
    private $_display_type_handlers = array();
    private $_default_display_type_handler = NULL;
    private $css_class = 'ngg-trigger-buttons';
    private $_default_image_types = array(
        'photocrati-nextgen_basic_thumbnails',
        'photocrati-nextgen_basic_singlepic',
        'photocrati-nextgen_pro_thumbnail_grid',
        'photocrati-nextgen_pro_blog_gallery',
        'photocrati-nextgen_pro_film'
    );

    /**
     * @return C_Displayed_Gallery_Trigger_Manager
     */
    static function get_instance()
    {
        if (!self::$_instance) {
            $klass = get_class();
            self::$_instance = new $klass;
        }
        return self::$_instance;
    }

    function __construct()
    {
        $this->_default_display_type_handler = 'C_Displayed_Gallery_Trigger_Handler';
        foreach ($this->_default_image_types as $display_type) {
            $this->register_display_type_handler($display_type, 'C_Displayed_Gallery_Image_Trigger_Handler');
        }
    }

    function register_display_type_handler($display_type, $klass)
    {
        $this->_display_type_handlers[$display_type] = $klass;
    }

    function deregister_display_type_handler($display_type)
    {
        unset($this->_display_type_handlers[$display_type]);
    }

    function add($name, $handler)
    {
        $this->_triggers[$name] = $handler;
        $this->_trigger_order[] = $name;

        return $this;
    }

    function remove($name)
    {
        $order = array();
        unset($this->_triggers[$name]);
        foreach ($this->_trigger_order as $trigger) {
            if ($trigger != $name) $order[] = $trigger;
        }
        $this->_trigger_order = $order;

        return $this;
    }

    function _rebuild_index()
    {
        $order = array();
        foreach ($this->_trigger_order as $name) {
            $order[] = $name;
        }
        $this->_trigger_order = $order;

        return $this;
    }

    function increment_position($name)
    {
       if (($current_index = array_search($name, $this->_trigger_order)) !== FALSE) {
           $next_index = $current_index += 1;

           // 1,2,3,4,5 => 1,2,4,3,5
           if (isset($this->_trigger_order[$next_index])) {
               $next = $this->_trigger_order[$next_index];
               $this->_trigger_order[$next_index] = $name;
               $this->_trigger_order[$current_index] = $next;
           }
       }

        return $this->position_of($name);
    }

    function decrement_position($name)
    {
        if (($current_index = array_search($name, $this->_trigger_order)) !== FALSE) {
            $previous_index = $current_index -= 1;
            if (isset($this->_trigger_order[$previous_index])) {
                $previous = $this->_trigger_order[$previous_index];
                $this->_trigger_order[$previous_index] = $name;
                $this->_trigger_order[$current_index] = $previous;
            }
        }

        return $this->position_of($name);
    }

    function position_of($name)
    {
        return array_search($name, $this->_trigger_order);
    }

    function move_to_position($name, $position_index)
    {
        if (($current_index = $this->position_of($name)) !== FALSE) {
            $func = 'increment_position';
            if ($current_index < $position_index) $func = 'decrement_position';
            while ($this->position_of($name) != $position_index) {
                $this->$func($name);
            }
        }

        return $this->position_of($name);
    }

    function move_to_start($name)
    {
        if (($index = $this->position_of($name))) {
            unset($this->_trigger_order[$index]);
            array_unshift($this->_trigger_order, $name);
            $this->_rebuild_index();
        }

        return $this->position_of($name);
    }

    function count()
    {
        return count($this->_trigger_order);
    }

    function move_to_end($name)
    {
        $index = $this->position_of($name);
        if ($index !== FALSE OR $index != $this->count()-1) {
            unset($this->_trigger_order[$index]);
            $this->_trigger_order[] = $name;
            $this->_rebuild_index();
        }

        return $this->position_of($name);
    }

    function get_handler_for_displayed_gallery($displayed_gallery)
    {
        // Find the trigger handler for the current display type.

        // First, check the display settings for the displayed gallery. Some third-party
        // display types might specify their own handler
        $klass = NULL;
        if (isset($displayed_gallery->display_settings['trigger_handler'])) {
            $klass = $displayed_gallery->display_settings['trigger_handler'];
        }

        // Check if a handler has been registered
        else {
            $klass = $this->_default_display_type_handler;
            if (isset($this->_display_type_handlers[$displayed_gallery->display_type])) {
                $klass = $this->_display_type_handlers[$displayed_gallery->display_type];
            }
        }

        return $klass;
    }

    function render($view, $displayed_gallery)
    {
        if (($klass = $this->get_handler_for_displayed_gallery($displayed_gallery))) {
            $handler = new $klass;
            $handler->view = $view;
            $handler->displayed_gallery = $displayed_gallery;
            $handler->manager = $this;
            if (method_exists($handler, 'render')) {
                $handler->render();
            }
        }

        return $view;
    }

    function render_trigger($name, $view, $displayed_gallery)
    {
        $retval = '';

        if (isset($this->_triggers[$name])) {
            $klass = $this->_triggers[$name];
            if (call_user_func(array($klass, 'is_renderable'), $name, $displayed_gallery)) {
                $handler                    = new $klass;
                $handler->name              = $name;
                $handler->view              = $this->view = $view;
                $handler->displayed_gallery = $displayed_gallery;
                $retval = $handler->render();
            }
        }

        return $retval;
    }

    function render_triggers($view, $displayed_gallery)
    {
        $output     = FALSE;
        $css_class  = esc_attr($this->css_class);
        $retval     = array("<div class='{$css_class}'>");

        foreach ($this->_trigger_order as $name) {
            if (($markup = $this->render_trigger($name, $view, $displayed_gallery))) {
                $output  = TRUE;
                $retval[] = $markup;
            }
        }

        if ($output) {
            $retval[] = "</div>";
            $retval = implode("\n", $retval);
        }
        else {
            $retval = '';
        }

        return $retval;
    }

    function enqueue_resources($displayed_gallery)
    {
        if (($handler = $this->get_handler_for_displayed_gallery($displayed_gallery))) {
            wp_enqueue_style('fontawesome');
            wp_enqueue_style('ngg_trigger_buttons');

            if (method_exists($handler, 'enqueue_resources')) {
                call_user_func(array($handler, 'enqueue_resources'), $displayed_gallery);
                foreach ($this->_trigger_order as $name) {
                    $handler = $this->_triggers[$name];
                    $renderable = TRUE;
                    if (method_exists($handler, 'is_renderable')) {
                        $renderable = call_user_func($handler, 'is_renderable', $name, $displayed_gallery);
                    }

                    if ($renderable && method_exists($handler, 'enqueue_resources')) {
                        call_user_func(array($handler, 'enqueue_resources', $name, $displayed_gallery));
                    }
                }
            }
        }
    }
}

class C_Displayed_Gallery_Image_Trigger_Handler
{
    function render()
    {
        foreach ($this->view->find('nextgen_gallery.image', true) as $image_element) {
            $image_element->append($this->manager->render_triggers($image_element, $this->displayed_gallery));
        }
    }
}

class C_Displayed_Gallery_Trigger_Handler
{
    function render()
    {
        $this->view->append($this->manager->render_triggers($this->view, $this->displayed_gallery));
    }
}