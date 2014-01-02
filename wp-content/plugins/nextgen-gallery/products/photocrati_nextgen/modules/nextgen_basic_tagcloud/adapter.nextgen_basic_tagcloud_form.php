<?php

class A_NextGen_Basic_Tagcloud_Form extends Mixin_Display_Type_Form
{
	function get_display_type_name()
	{
		return NEXTGEN_BASIC_TAG_CLOUD_MODULE_NAME;
	}


    function _get_field_names()
    {
        return array(
            'nextgen_basic_tagcloud_display_type'
        );
    }

    function _render_nextgen_basic_tagcloud_display_type_field($display_type)
    {
        $types = array();
        $skip_types = array(
            'photocrati-nextgen_basic_tagcloud',
            'photocrati-nextgen_basic_singlepic'
        );
        $mapper = $this->object->get_registry()->get_utility('I_Display_Type_Mapper');
        $display_types = $mapper->find_all();
        foreach ($display_types as $dt) {
			if (!isset($dt->name)) die(var_dump($dt));
            if (in_array($dt->name, $skip_types)) continue;
            $types[$dt->name] = str_replace('NextGEN Basic ', '', $dt->title);
        }

        return $this->_render_select_field(
            $display_type,
            'display_type',
            'Display type',
            $types,
            $display_type->settings['display_type'],
            'The display type that the tagcloud will point its results to'
        );
    }
}
