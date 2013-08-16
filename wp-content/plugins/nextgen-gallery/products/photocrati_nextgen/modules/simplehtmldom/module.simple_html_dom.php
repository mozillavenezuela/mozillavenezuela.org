<?php

/***
	{
		Module: photocrati-simple_html_dom
	}
***/

if (!function_exists(('file_get_html'))) require_once('simplehtmldom/simple_html_dom.php');

class M_Simple_Html_Dom extends C_Base_Module
{
    function define()
    {
        parent::define(
            'photocrati-simple_html_dom',
            'Simple HTML Dom',
            'Provides the simple_html_dom utility for other modules to use',
            '1.5',
            'http://www.photocrati.com',
            'Photocrati Media',
            'http://www.photocrati.com'
        );
    }

		function get_type_list()
		{
			return array(
			);
		}
}

new M_Simple_Html_Dom();
