<?php

/***
{
    Module: photocrati-third_party_compat,
    Depends: {}
}
 ***/
class M_Third_Party_Compat extends C_Base_Module
{
    function define()
    {
        parent::define(
            'photocrati-third_party_compat',
            'Third Party Compatibility',
            "Adds Third party compatibility hacks, adjustments, and modifications",
            '0.1',
            'http://www.nextgen-gallery.com',
            'Photocrati Media',
            'http://www.photocrati.com'
        );
    }

    function _register_adapters()
    {
    }

    function _register_hooks()
    {
        add_action('init', array(&$this, 'colorbox'), PHP_INT_MAX);
        add_action('wp', array(&$this, 'bjlazyload'), PHP_INT_MAX);
    }

    /**
     * Colorbox fires a filter (pri=100) to add class attributes to images via a the_content filter. We fire our
     * shortcodes at PHP_INT_MAX-1 to avoid encoding issues with some themes. Here we move the Colorbox filters
     * priority to PHP_INT_MAX so that they run after our shortcode text has been replaced with rendered galleries.
     */
    function colorbox()
    {
        if (!class_exists('JQueryColorboxFrontend'))
            return;

        global $wp_filter;

        if (empty($wp_filter['the_content'][100]))
            return;

        foreach ($wp_filter['the_content'][100] as $id => $filter) {
            if (!strpos($id, 'addColorboxGroupIdToImages'))
                continue;

            $object = $filter['function'][0];

            if (get_class($object) != 'JQueryColorboxFrontend')
                continue;

            remove_filter('the_content', array($object, 'addColorboxGroupIdToImages'), 100);
            remove_filter('the_excerpt', array($object, 'addColorboxGroupIdToImages'), 100);
            add_filter('the_content', array($object, 'addColorboxGroupIdToImages'), PHP_INT_MAX);
            add_filter('the_excerpt', array($object, 'addColorboxGroupIdToImages'), PHP_INT_MAX);
            break;
        }
    }

    /**
     * For the same reasons as Colorbox we move BJ-Lazy-load's filter() method to a later priority so it can access
     * our rendered galleries.
     */
    function bjlazyload()
    {
        if (!class_exists('BJLL'))
            return;

        global $wp_filter;

        if (empty($wp_filter['the_content'][200]))
            return;

        foreach ($wp_filter['the_content'][200] as $id => $filter) {
            if (!strpos($id, 'filter'))
                continue;

            $object = $filter['function'][0];

            if (get_class($object) != 'BJLL')
                continue;

            remove_filter('the_content', array($object, 'filter'), 200);
            add_filter('the_content', array($object, 'filter'), PHP_INT_MAX);
            break;
        }

        add_filter('the_content', array($this, 'bjlazyload_filter'), PHP_INT_MAX-1);
    }

    /**
     * BJ-Lazy-load's regex is lazy and doesn't handle multiline search or instances where <img is immediately followed
     * by a newline. The following regex replaces newlines and strips unnecessary space. We fire this filter
     * before BJ-Lazy-Load's to make our galleries compatible with its expectations.
     *
     * @param string $content
     * @return string
     */
    function bjlazyload_filter($content)
    {
        return trim(preg_replace("/\s\s+/", " ", $content));
    }

    function get_type_list()
    {
        return array(
        );
    }
}

new M_Third_Party_Compat();