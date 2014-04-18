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
            '0.3',
            'http://www.nextgen-gallery.com',
            'Photocrati Media',
            'http://www.photocrati.com'
        );

        // the following constants were renamed for 2.0.41; keep them declared for compatibility sake until
        // other parties can update themselves.
        $changed_constants = array(
            'NEXTGEN_ADD_GALLERY_SLUG'                     => 'NGG_ADD_GALLERY_SLUG',
            'NEXTGEN_BASIC_SINGLEPIC_MODULE_NAME'          => 'NGG_BASIC_SINGLEPIC',
            'NEXTGEN_BASIC_TAG_CLOUD_MODULE_NAME'          => 'NGG_BASIC_TAGCLOUD',
            'NEXTGEN_DISPLAY_PRIORITY_BASE'                => 'NGG_DISPLAY_PRIORITY_BASE',
            'NEXTGEN_DISPLAY_PRIORITY_STEP'                => 'NGG_DISPLAY_PRIORITY_STEP',
            'NEXTGEN_DISPLAY_SETTINGS_SLUG'                => 'NGG_DISPLAY_SETTINGS_SLUG',
            'NEXTGEN_FS_ACCESS_SLUG'                       => 'NGG_FS_ACCESS_SLUG',
            'NEXTGEN_GALLERY_ATTACH_TO_POST_SLUG'          => 'NGG_ATTACH_TO_POST_SLUG',
            'NEXTGEN_GALLERY_BASIC_SLIDESHOW'              => 'NGG_BASIC_SLIDESHOW',
            'NEXTGEN_GALLERY_BASIC_THUMBNAILS'             => 'NGG_BASIC_THUMBNAILS',
            'NEXTGEN_GALLERY_CHANGE_OPTIONS_CAP'           => 'NGG_CHANGE_OPTIONS_CAP',
            'NEXTGEN_GALLERY_I8N_DOMAIN'                   => 'NGG_I8N_DOMAIN',
            'NEXTGEN_GALLERY_IMPORT_ROOT'                  => 'NGG_IMPORT_ROOT',
            'NEXTGEN_GALLERY_MODULE_DIR'                   => 'NGG_MODULE_DIR',
            'NEXTGEN_GALLERY_MODULE_URL'                   => 'NGG_MODULE_URL',
            'NEXTGEN_GALLERY_NEXTGEN_BASIC_COMPACT_ALBUM'  => 'NGG_BASIC_COMPACT_ALBUM',
            'NEXTGEN_GALLERY_NEXTGEN_BASIC_EXTENDED_ALBUM' => 'NGG_BASIC_EXTENDED_ALBUM',
            'NEXTGEN_GALLERY_NEXTGEN_BASIC_IMAGEBROWSER'   => 'NGG_BASIC_IMAGEBROWSER',
            'NEXTGEN_GALLERY_NGGLEGACY_MOD_DIR'            => 'NGG_LEGACY_MOD_DIR',
            'NEXTGEN_GALLERY_NGGLEGACY_MOD_URL'            => 'NGG_LEGACY_MOD_URL',
            'NEXTGEN_GALLERY_PLUGIN'                       => 'NGG_PLUGIN',
            'NEXTGEN_GALLERY_PLUGIN_BASENAME'              => 'NGG_PLUGIN_BASENAME',
            'NEXTGEN_GALLERY_PLUGIN_DIR'                   => 'NGG_PLUGIN_DIR',
            'NEXTGEN_GALLERY_PLUGIN_STARTED_AT'            => 'NGG_PLUGIN_STARTED_AT',
            'NEXTGEN_GALLERY_PLUGIN_URL'                   => 'NGG_PLUGIN_URL',
            'NEXTGEN_GALLERY_PLUGIN_VERSION'               => 'NGG_PLUGIN_VERSION',
            'NEXTGEN_GALLERY_PRODUCT_DIR'                  => 'NGG_PRODUCT_DIR',
            'NEXTGEN_GALLERY_PRODUCT_URL'                  => 'NGG_PRODUCT_URL',
            'NEXTGEN_GALLERY_PROTECT_IMAGE_MOD_STATIC_URL' => 'NGG_PROTUCT_IMAGE_MOD_STATIC_URL',
            'NEXTGEN_GALLERY_PROTECT_IMAGE_MOD_URL'        => 'NGG_PROTECT_IMAGE_MOD_URL',
            'NEXTGEN_GALLERY_TESTS_DIR'                    => 'NGG_TESTS_DIR',
            'NEXTGEN_LIGHTBOX_ADVANCED_OPTIONS_SLUG'       => 'NGG_LIGHTBOX_ADVANCED_OPTIONS_SLUG',
            'NEXTGEN_LIGHTBOX_OPTIONS_SLUG'                => 'NGG_LIGHTBOX_OPTIONS_SLUG',
            'NEXTGEN_OTHER_OPTIONS_SLUG'                   => 'NGG_OTHER_OPTIONS_SLUG'
        );
        foreach ($changed_constants as $old => $new) {
            if (defined($new) && !defined($old)) {
                define($old, constant($new));
            }
        }
    }

    function _register_adapters()
    {
    }

    function _register_hooks()
    {
        add_action('init', array(&$this, 'colorbox'),   PHP_INT_MAX);
        add_action('init', array(&$this, 'flattr'),     PHP_INT_MAX);
        add_action('wp',   array(&$this, 'bjlazyload'), PHP_INT_MAX);

        add_action('plugins_loaded', array(&$this, 'wpml'), PHP_INT_MAX);

        add_filter('headway_gzip', array(&$this, 'headway_gzip'), (PHP_INT_MAX - 1));
        add_filter('get_translatable_documents', array(&$this, 'wpml_translatable_documents'));
        add_filter('the_content', array(&$this, 'check_weaverii'), -(PHP_INT_MAX-2));
        add_action('wp', array(&$this, 'check_for_jquery_lightbox'));

        // TODO: Only needed for NGG Pro 1.0.10 and lower
        add_action('the_post', array(&$this, 'add_ngg_pro_page_parameter'));
    }

    function check_for_jquery_lightbox()
    {
        // Fix for jQuery Lightbox: http://wordpress.org/plugins/wp-jquery-lightbox/
        // jQuery Lightbox tries to modify the content of a post, but it does so before we modify
        // the content, and therefore it's modifications have no effect on our galleries
        if (function_exists('jqlb_autoexpand_rel_wlightbox')) {
            $settings = C_NextGen_Settings::get_instance();

            // First, we make it appear that NGG has no lightbox effect enabled. That way
            // we don't any lightbox resources
            unset($settings->thumbEffect);

            // We would normally just let the third-party plugin do it's thing, but it's regex doesn't
            // seem to work on our <a> tags (perhaps because they span multiple of lines or have data attributes)
            // So instead, we just do what the third-party plugin wants - add the rel attribute
            $settings->thumbCode="rel='lightbox[%POST_ID%]'";
        }
    }

    /**
     * Weaver II's 'weaver_show_posts' shortcode creates a new wp-query, causing a second round of 'the_content'
     * filters to apply. This checks for WeaverII and enables all NextGEN shortcodes that would otherwise be left
     * disabled by our shortcode manager. See https://core.trac.wordpress.org/ticket/17817 for more.
     *
     * @param $content
     * @return $content
     */
    function check_weaverii($content)
    {
        if (function_exists('weaverii_show_posts_shortcode'))
            C_NextGen_Shortcode_Manager::get_instance()->activate_all();

        return $content;
    }

    /**
     * WPML assigns an action to 'init' that *may* enqueue some admin-side JS. This JS relies on some inline JS
     * to be injected that isn't present in ATP so for ATP requests ONLY we disable their action that enqueues
     * their JS files.
     */
    function wpml()
    {
        if (!class_exists('SitePress'))
            return;

        if (FALSE === strpos(strtolower($_SERVER['REQUEST_URI']), '/nextgen-attach_to_post'))
            return;

        global $wp_filter;

        if (empty($wp_filter['init'][2]))
            return;

        foreach ($wp_filter['init'][2] as $id => $filter) {
            if (!strpos($id, 'js_load'))
                continue;

            $object = $filter['function'][0];

            if (get_class($object) != 'SitePress')
                continue;

            remove_action('init', array($object, 'js_load'), 2);
        }
    }

    /**
     * NextGEN stores some data in custom posts that MUST NOT be automatically translated by WPML
     *
     * @param array $icl_post_types
     * @return array $icl_post_types without any NextGEN custom posts
     */
    function wpml_translatable_documents($icl_post_types = array())
    {
        $nextgen_post_types = array(
            'ngg_album',
            'ngg_gallery',
            'ngg_pictures',
            'displayed_gallery',
            'display_type',
            'gal_display_source',
            'lightbox_library',
            'photocrati-comments'
        );
        foreach ($icl_post_types as $ndx => $post_type) {
            if (in_array($post_type->name, $nextgen_post_types))
                unset($icl_post_types[$ndx]);
        }
        return $icl_post_types;
    }

    /**
     * NGG Pro 1.0.10 relies on the 'page' parameter for pagination, but that conflicts with
     * WordPress Post Pagination (<!-- nextpage -->). This was fixed in 1.0.11, so this code is
     * for backwards compatibility
     * TODO: This can be removed in a later release
     */
    function add_ngg_pro_page_parameter()
    {
        global $post;

        if ($post AND (strpos($post->content, "<!--nextpage-->") === FALSE) AND (strpos($_SERVER['REQUEST_URI'], '/page/') !== FALSE)) {
            if (preg_match("#/page/(\\d+)#", $_SERVER['REQUEST_URI'], $match)) {
                $_REQUEST['page'] = $match[1];
            }
        }
    }

    /**
     * Headway themes offer gzip compression, but it causes problems with NextGEN output. Disable that feature while
     * NextGEN is active.
     *
     * @param $option
     * @return bool
     */
    function headway_gzip($option)
    {
        if (!class_exists('HeadwayOption'))
            return $option;

        return FALSE;
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
     * Flattr fires a filter (pri=32767) on "the_content" that recurses. This causes problems,
     * see https://core.trac.wordpress.org/ticket/17817 for more information. Moving their filter to PHP_INT_MAX
     * is enough for us though
     */
    function flattr()
    {
        if (!class_exists('Flattr'))
            return;

        global $wp_filter;

        $level = 32767;

        if (empty($wp_filter['the_content'][$level]))
            return;

        foreach ($wp_filter['the_content'][$level] as $id => $filter) {
            if (!strpos($id, 'injectIntoTheContent'))
                continue;

            $object = $filter['function'][0];

            if (get_class($object) != 'Flattr')
                continue;

            remove_filter('the_content', array($object, 'injectIntoTheContent'), $level);
            add_filter('the_content', array($object, 'injectIntoTheContent'), PHP_INT_MAX);
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
