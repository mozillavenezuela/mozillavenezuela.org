<?php

class Mixin_NextGen_Basic_Album_Form extends Mixin_Display_Type_Form
{
	function _get_field_names()
	{
		return array(
            'nextgen_basic_album_gallery_display_type',
            'nextgen_basic_templates_template',
        );
	}

    /**
     * Renders the Gallery Display Type field
     * @param C_Display_Type $display_type
     */
    function _render_nextgen_basic_album_gallery_display_type_field($display_type)
    {
        $mapper = $this->object->get_registry()->get_utility('I_Display_Type_Mapper');

        return $this->render_partial(
            'photocrati-nextgen_basic_album#nextgen_basic_album_gallery_display_type',
            array(
                'display_type_name'             =>  $display_type->name,
                'gallery_display_type_label'    =>  _('Display galleries as'),
                'gallery_display_type_help'     =>  _('How would you like galleries to be displayed?'),
                'gallery_display_type'          =>  $display_type->settings['gallery_display_type'],
                'galleries_per_page_label'      =>  _('Galleries per page'),
                'galleries_per_page'            =>  $display_type->settings['galleries_per_page'],
                'display_types'                 =>  $mapper->find_by_entity_type('image')
            ),
            TRUE
        );
    }


    /**
     * Renders the Galleries Per Page field
     * @param C_Display_Type $display_type
     */
    function _render_nextgen_basic_album_galleries_per_page_field($display_type)
    {
        return $this->render_partial(
            'photocrati-nextgen_basic_album#nextgen_basic_album_galleries_per_page',
            array(
                'display_type_name'             =>  $display_type->name,
                'galleries_per_page_label'      =>  _('Items per page'),
                'galleries_per_page_help'       =>  _('Maximum number of galleries or sub-albums to appear on a single page'),
                'galleries_per_page'            =>  $display_type->settings['galleries_per_page']
            ),
            TRUE
        );
    }
}