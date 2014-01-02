<?php
/***
{
		Module: photocrati-cache
}
***/
class M_Cache extends C_Base_Module
{
    /**
     * Defines the module name & version
     */
    function define()
	{
		parent::define(
			'photocrati-cache',
			'Cache',
			'Handles clearing of NextGen caches',
			'0.2',
			'http://www.nextgen-gallery.com',
			'Photocrati Media',
			'http://www.photocrati.com'
		);
	}

    /**
     * Register utilities
     */
    function _register_utilities()
    {
        $this->get_registry()->add_utility('I_Cache', 'C_Cache');
    }

    function get_type_list()
    {
        return array(
            'C_Cache' => 'class.cache.php',
            'I_Cache' => 'interface.cache.php'
        );
    }
}

new M_Cache();
