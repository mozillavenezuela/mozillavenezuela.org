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
			'0.2',
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
		add_action('template_redirect', array(&$this, 'restore_request_uri'), 1);
	}

	function restore_request_uri()
	{
		if (isset($_SERVER['ORIG_REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = $_SERVER['ORIG_REQUEST_URI'];
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