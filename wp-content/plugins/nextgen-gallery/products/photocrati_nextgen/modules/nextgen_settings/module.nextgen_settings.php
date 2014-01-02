<?php

/***
{
	Module:	photocrati-nextgen_settings
}
***/

class M_NextGen_Settings extends C_Base_Module
{
	/**
	 * Defines the module
	 */
	function define()
	{
		parent::define(
			'photocrati-nextgen_settings',
			'NextGEN Gallery Settings',
			'Provides central management for NextGEN Gallery settings',
			'0.3',
			'http://www.nextgen-gallery.com',
			'Photocrati Media',
			'http://www.photocrati.com'
		);

		include_once('class.nextgen_settings_installer.php');
		C_Photocrati_Installer::add_handler($this->module_id, 'C_NextGen_Settings_Installer');
	}

    function get_type_list()
    {
        return array(
            'C_NextGen_Settings_Installer' => 'class.nextgen_settings_installer.php'
        );
    }
}

new M_NextGen_Settings();
