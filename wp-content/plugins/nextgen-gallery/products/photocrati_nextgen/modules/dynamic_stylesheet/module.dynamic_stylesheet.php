<?php

/*
{
	Module: photocrati-dynamic_stylesheet,
	Depends: { photocrati-mvc, photocrati-lzw }
}
 */
class M_Dynamic_Stylesheet extends C_Base_Module
{
	function define($context=FALSE)
	{
		parent::define(
			'photocrati-dynamic_stylesheet',
			'Dynamic Stylesheet',
			'Provides the ability to generate and enqueue a dynamic stylesheet',
			'0.3',
			'http://www.nextgen-gallery.com',
			'Photocrati Media',
			'http://www.photocrati.com',
			$context
		);

		include_once('class.dynamic_stylesheet_installer.php');
		C_Photocrati_Installer::add_handler($this->module_id, 'C_Dynamic_Stylesheet_Installer');
	}

	function _register_utilities()
	{
		$this->get_registry()->add_utility(
			"I_Dynamic_Stylesheet", 'C_Dynamic_Stylesheet_Controller'
		);
	}

	function _register_adapters()
	{
		$this->get_registry()->add_adapter(
			'I_Router', 'A_Dynamic_Stylesheet_Routes'
		);
	}

    function get_type_list()
    {
        return array(
            'A_Dynamic_Stylesheet_Routes' 		=> 'adapter.dynamic_stylesheet_routes.php',
			'C_Dynamic_Stylesheet_Installer'	=> 'class.dynamic_stylesheet_installer.php',
            'C_Dynamic_Stylesheet_Controller' 	=> 'class.dynamic_stylesheet_controller.php',
            'I_Dynamic_Stylesheet' 				=> 'interface.dynamic_stylesheet.php'
        );
    }
}

new M_Dynamic_Stylesheet;
