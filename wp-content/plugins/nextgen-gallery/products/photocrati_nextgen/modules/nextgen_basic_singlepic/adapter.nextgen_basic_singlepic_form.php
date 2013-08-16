<?php

class A_NextGen_Basic_SinglePic_Form extends Mixin_Display_Type_Form
{
	/**
	 * Returns the name of the display type
	 * @return string
	 */
	function get_display_type_name()
	{
		return NEXTGEN_BASIC_SINGLEPIC_MODULE_NAME;
	}

	/**
	 * Returns the name of the fields to render for the SinglePic
	 */
	function _get_field_names()
	{
		return array(
            'nextgen_basic_singlepic_dimensions',
            'nextgen_basic_singlepic_link',
            'nextgen_basic_singlepic_float',
            'nextgen_basic_singlepic_quality',
            'nextgen_basic_singlepic_crop',
            'nextgen_basic_singlepic_display_watermark',
            'nextgen_basic_singlepic_display_reflection',
            'nextgen_basic_templates_template'
        );
	}

	    function _render_nextgen_basic_singlepic_dimensions_field($display_type)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_basic_singlepic#nextgen_basic_singlepic_settings_dimensions',
            array(
                'display_type_name' => $display_type->name,
                'dimensions_label' => _('Thumbnail dimensions'),
                'width_label' => _('Width'),
                'width' => $display_type->settings['width'],
                'height_label' => _('Width'),
                'height' => $display_type->settings['height'],
            ),
            True
        );
    }

    function _render_nextgen_basic_singlepic_link_field($display_type)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_basic_singlepic#nextgen_basic_singlepic_settings_link',
            array(
                'display_type_name' => $display_type->name,
                'link_label' => _('Link'),
                'link' => $display_type->settings['link'],
            ),
            True
        );
    }

    function _render_nextgen_basic_singlepic_quality_field($display_type)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_basic_singlepic#nextgen_basic_singlepic_settings_quality',
            array(
                'display_type_name' => $display_type->name,
                'quality_label' => _('Image quality'),
                'quality' => $display_type->settings['quality'],
            ),
            True
        );
    }

    function _render_nextgen_basic_singlepic_display_watermark_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'display_watermark',
            'Display watermark',
            $display_type->settings['display_watermark']
        );
    }

    function _render_nextgen_basic_singlepic_display_reflection_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'display_reflection',
            'Display reflection',
            $display_type->settings['display_reflection']
        );
    }

    function _render_nextgen_basic_singlepic_crop_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'crop',
            'Crop thumbnail',
            $display_type->settings['crop']
        );
    }

    function _render_nextgen_basic_singlepic_float_field($display_type)
    {
        return $this->_render_select_field(
            $display_type,
            'float',
            'Float',
            array('' => 'None', 'left' => 'Left', 'right' => 'Right'),
            $display_type->settings['float']
        );
    }
}