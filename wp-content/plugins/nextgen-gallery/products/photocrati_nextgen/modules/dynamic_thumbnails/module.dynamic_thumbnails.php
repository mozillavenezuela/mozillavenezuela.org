<?php

/***
 {
	Module: photocrati-dynamic_thumbnails
 }
 ***/
class M_Dynamic_Thumbnails extends C_Base_Module
{
	function define()
	{
		parent::define(
			'photocrati-dynamic_thumbnails',
			'Dynamic Thumbnails',
			'Adds support for dynamic thumbnails',
			'0.5',
			'http://www.nextgen-gallery.com',
			'Photocrati Media',
			'http://www.photocrati.com'
		);

		include_once('class.dynamic_thumbnails_installer.php');
		C_Photocrati_Installer::add_handler($this->module_id, 'C_Dynamic_Thumbnails_Installer');
	}

	function _register_adapters()
	{
		$this->get_registry()->add_adapter('I_Router', 'A_Dynamic_Thumbnail_Routes');
		$this->get_registry()->add_adapter('I_GalleryStorage_Driver', 'A_Dynamic_Thumbnails_Storage_Driver');
	}

	function _register_utilities()
	{
        $this->get_registry()->add_utility('I_Dynamic_Thumbnails_Manager', 'C_Dynamic_Thumbnails_Manager');
        $this->get_registry()->add_utility('I_Dynamic_Thumbnails_Controller', 'C_Dynamic_Thumbnails_Controller');
	}

    function get_type_list()
    {
        return array(
            'A_Dynamic_Thumbnails_Storage_Driver'	=> 'adapter.dynamic_thumbnails_storage_driver.php',
            'A_Dynamic_Thumbnail_Routes' 			=> 'adapter.dynamic_thumbnail_routes.php',
            'C_Dynamic_Thumbnails_Installer'		=> 'class.dynamic_thumbnails_installer.php',
            'C_Dynamic_Thumbnails_Controller' 		=> 'class.dynamic_thumbnails_controller.php',
            'C_Dynamic_Thumbnails_Manager' 			=> 'class.dynamic_thumbnails_manager.php',
            'I_Dynamic_Thumbnails_Controller' 		=> 'interface.dynamic_thumbnails_controller.php',
            'I_Dynamic_Thumbnails_Manager' 			=> 'interface.dynamic_thumbnails_manager.php'
        );
    }

}

new M_Dynamic_Thumbnails();
