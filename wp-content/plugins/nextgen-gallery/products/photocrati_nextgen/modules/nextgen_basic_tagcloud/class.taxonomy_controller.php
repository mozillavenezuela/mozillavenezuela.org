<?php

class C_Taxonomy_Controller extends C_MVC_Controller
{
    static $_instances = array();
    protected $ngg_tag_detection_has_run = FALSE;

    /**
     * Returns an instance of this class
     *
     * @param string $context
     * @return C_Taxonomy_Controller
     */
    static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context]))
        {
            $klass = get_class();
            self::$_instances[$context] = new $klass($context);
        }
        return self::$_instances[$context];
    }

    function define($context = FALSE)
    {
        parent::define($context);
        $this->implement('I_Taxonomy_Controller');
    }

    /**
     * Returns the rendered HTML of a gallery based on the provided tag
     *
     * @param string $tag
     * @return string
     */
    function index_action($tag)
    {
        $renderer = $this->object->get_registry()->get_utility('I_Displayed_Gallery_Renderer');
        $output = $renderer->display_images(array(
            'source' => 'tags',
            'container_ids' => $tag,
            'slug' => $tag,
            'display_type' => NGG_BASIC_THUMBNAILS
        ));

        // This strips extra whitespace and strips newlines. For some reason this is especially
        // necessary on Wordpress taxonomy pages.
        return trim(preg_replace("/\s\s+/", " ", $output));
    }

    /**
     * Determines if the current page is /ngg_tag/{*}
     *
     * @param $posts Wordpress post objects
     * @return array Wordpress post objects
     */
    function detect_ngg_tag($posts, $wp_query_local)
    {
        global $wp;
        global $wp_query;
        $wp_query_orig = false;
        
        if ($wp_query_local != null && $wp_query_local != $wp_query) {
        	$wp_query_orig = $wp_query;
        	$wp_query = $wp_query_local;
        }

        // This appears to be necessary for multisite installations, but I can't imagine why. More hackery..
        $tag = (get_query_var('ngg_tag') ? get_query_var('ngg_tag') : get_query_var('name'));

        if (!$this->ngg_tag_detection_has_run // don't run more than once; necessary for certain themes
        &&  !is_admin() // will destroy 'view all posts' page without this
        &&  !empty($tag) // only run when a tag has been given to wordpress
        &&  (stripos($wp->request, 'ngg_tag') === 0 // make sure the query begins with /ngg_tag
             || (isset($wp_query->query_vars['page_id'])
                  && $wp_query->query_vars['page_id'] === 'ngg_tag')
            )
           )
        {
            $this->ngg_tag_detection_has_run = TRUE;

            // Wordpress somewhat-correctly generates several notices, so silence them as they're really unnecessary
            if (!defined('WP_DEBUG') || !WP_DEBUG)
                error_reporting(0);

            // create in-code a fake post; we feed it back to Wordpress as the sole result of the "the_posts" filter
            $posts = NULL;
            $posts[] = $this->create_ngg_tag_post($tag);

            $wp_query->is_404 = FALSE;
            $wp_query->is_page = TRUE;
            $wp_query->is_singular = TRUE;
            $wp_query->is_home = FALSE;
            $wp_query->is_archive = FALSE;
            $wp_query->is_category = FALSE;

            unset($wp_query->query['error']);
            $wp_query->query_vars['error'] = '';
        }
        
        if ($wp_query_orig !== false) {
        	$wp_query = $wp_query_orig;
        }

        return $posts;
    }

    function create_ngg_tag_post($tag)
    {
        $post = new stdClass;
        $post->post_author = FALSE;
        $post->post_name = 'ngg_tag';
        $post->guid = get_bloginfo('wpurl') . '/' . 'ngg_tag';
        $post->post_title = "Images tagged &quot;{$tag}&quot;";
        $post->post_content = $this->index_action($tag);
        $post->ID = FALSE;
        $post->post_type = 'page';
        $post->post_status = 'static';
        $post->comment_status = 'closed';
        $post->ping_status = 'closed';
        $post->comment_count = 0;
        $post->post_date = current_time('mysql');
        $post->post_date_gmt = current_time('mysql', 1);

        return($post);
    }
}
