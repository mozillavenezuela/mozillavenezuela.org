<?php

/***
	{
		Module: photocrati-mvc,
		Depends: { photocrati-router, photocrati-nextgen_settings }
	}
***/

/**
 * TODO: The file below should be deprecated. We should use an example template
 * engine, such as Twig
 */
require_once('template_helper.php');

class M_MVC extends C_Base_Module
{
    function define()
    {
        parent::define(
            "photocrati-mvc",
            "MVC Framework",
            "Provides an MVC architecture for the plugin to use",
            "0.4",
            "http://www.photocrati.com",
            "Photocrati Media",
            "http://www.photocrati.com"
        );

		include_once('class.mvc_installer.php');
		C_Photocrati_Installer::add_handler($this->module_id, 'C_Mvc_Installer');

		include_once('class.mvc_option_handler.php');
		C_NextGen_Settings::add_option_handler('C_Mvc_Option_Handler', array(
			'mvc_template_dir'
		));
    }

    function _register_utilities()
    {
		$this->get_registry()->add_utility('I_Http_Response', 'C_Http_Response_Controller');
    }

    function _register_adapters()
    {
            $this->get_registry()->add_adapter('I_Fs', 'A_MVC_Fs');
            $this->get_registry()->add_adapter('I_Router', 'A_MVC_Router');
            $this->get_registry()->add_adapter('I_Component_Factory', 'A_MVC_Factory');
    }

    function get_type_list()
    {
        return array(
            'A_Mvc_Factory' => 'adapter.mvc_factory.php',
            'A_Mvc_Fs' => 'adapter.mvc_fs.php',
            'A_Mvc_Router' => 'adapter.mvc_router.php',
            'C_Mvc_Installer' => 'class.mvc_installer.php',
            'C_Mvc_Controller' => 'class.mvc_controller.php',
            'C_Mvc_View' => 'class.mvc_view.php',
            'C_Mvc_View_Element' => 'class.mvc_view_element.php',
            'I_Mvc_Controller' => 'interface.mvc_controller.php',
            'I_Mvc_View' => 'interface.mvc_view.php'
        );
    }
}

new M_MVC();
