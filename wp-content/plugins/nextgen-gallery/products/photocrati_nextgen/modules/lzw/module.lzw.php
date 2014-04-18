<?php

/*
{
	Module: photocrati-lzw
}
 */
class M_Lzw extends C_Base_Module
{
	function define($context=FALSE)
	{
		parent::define(
			'photocrati-lzw',
			'LZW',
			'Provides LZW compression utility',
			'0.1',
			'http://www.nextgen-gallery.com',
			'Photocrati Media',
			'http://www.photocrati.com',
			$context
		);
	}

	function _register_utilities()
	{
		$this->get_registry()->add_utility('I_Lzw', 'C_Lzw');
	}

    function get_type_list()
    {
        return array(
            'C_Lzw' => 'class.lzw.php',
            'I_Lzw' => 'interface.lzw.php'
        );
    }
}

new M_Lzw;
