<?php
/*
{
	Module: photocrati-fs
}
 */
class M_Fs extends C_Base_Module
{
	function define()
	{
		parent::define(
			'photocrati-fs',
			'Filesystem',
			'Provides a filesystem abstraction layer for Pope modules',
			'0.4',
			'http://www.photocrati.com',
			'Photocrati Media',
			'http://www.photocrati.com'
		);
	}

	function _register_utilities()
	{
		$this->get_registry()->add_utility('I_Fs', 'C_Fs');
	}

    function get_type_list()
    {
        return array(
            'C_Fs' => 'class.fs.php',
            'I_Fs' => 'interface.fs.php'
        );
    }
}

new M_Fs;