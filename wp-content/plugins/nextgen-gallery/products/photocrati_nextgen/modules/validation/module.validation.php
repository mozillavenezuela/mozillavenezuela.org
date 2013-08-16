<?php

/***
{
	Module: photocrati-validation
}
***/

class M_Validation extends C_Base_Module
{
	function define()
	{
		parent::define(
			'photocrati-validation',
			'Validation',
			'Provides validation support for objects',
			'0.1',
			'http://www.nextgen-gallery.com',
			'Photocrati Media',
			'http://www.photocrati.com'
		);
	}

    function get_type_list()
    {
        return array(
            'Mixin_Validation' => 'mixin.validation.php'
        );
    }
}

new M_Validation();