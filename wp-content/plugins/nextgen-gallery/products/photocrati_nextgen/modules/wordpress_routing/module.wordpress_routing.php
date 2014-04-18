<?php

/***
 {
	Module: photocrati-wordpress_routing,
	Depends: { photocrati-router }
 }
 ***/
class M_WordPress_Routing extends C_Base_Module
{
	function define()
	{
		parent::define(
			'photocrati-wordpress_routing',
			'WordPress Routing',
			"Integrates the MVC module's routing implementation with WordPress",
			'0.5',
			'http://www.nextgen-gallery.com',
			'Photocrati Media',
			'http://www.photocrati.com'
		);
	}

	function _register_adapters()
	{
		$this->get_registry()->add_adapter('I_Router', 'A_WordPress_Router');
        $this->get_registry()->add_adapter('I_Routing_App', 'A_WordPress_Routing_App');
	}

	function _register_hooks()
	{
        remove_action( 'template_redirect', 'wp_old_slug_redirect');
        remove_action( 'template_redirect', 'redirect_canonical');
		add_action('template_redirect', array(&$this, 'restore_request_uri'), 1);
	}

    /**
     * When WordPress sees a url like http://foobar.com/nggallery/page/2/, it thinks that it is an
     * invalid url. Therefore, we modify the request uri before WordPress parses the request, and then
     * restore the request uri afterwards
     */
    function restore_request_uri()
	{
		if (isset($_SERVER['ORIG_REQUEST_URI'])) {
            $request_uri    = $_SERVER['ORIG_REQUEST_URI'];
            $_SERVER['UNENCODED_URL'] = $_SERVER['HTTP_X_ORIGINAL_URL'] = $_SERVER['REQUEST_URI'] = $request_uri;
		}
        else {
            wp_old_slug_redirect();
            redirect_canonical();
        }
	}

    function get_type_list()
    {
        return array(
            'A_Wordpress_Router' => 'adapter.wordpress_router.php',
            'A_Wordpress_Routing_App' => 'adapter.wordpress_routing_app.php'
        );
    }
}

new M_WordPress_Routing();