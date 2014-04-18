<?php

class A_NextGen_Basic_ImageBrowser_Form extends Mixin_Display_Type_Form
{
	function get_display_type_name()
	{
		return NGG_BASIC_IMAGEBROWSER;
	}

	/**
     * Returns a list of fields to render on the settings page
     */
    function _get_field_names()
    {
        return array(
			'ajax_pagination',
			'nextgen_basic_templates_template',
		);
    }
}