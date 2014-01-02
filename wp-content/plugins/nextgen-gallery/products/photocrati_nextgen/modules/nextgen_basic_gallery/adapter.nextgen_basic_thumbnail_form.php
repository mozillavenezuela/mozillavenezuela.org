<?php

class A_NextGen_Basic_Thumbnail_Form extends Mixin_Display_Type_Form
{
	function get_display_type_name()
	{
		return NEXTGEN_GALLERY_BASIC_THUMBNAILS;
	}

	/**
	 * Enqueues static resources required by this form
	 */
	function enqueue_static_resources()
	{
		wp_enqueue_style(
			'nextgen_basic_thumbnails_settings',
			$this->object->get_static_url('photocrati-nextgen_basic_gallery#thumbnails/nextgen_basic_thumbnails_settings.css'),
			false
		);

		wp_enqueue_script(
			'nextgen_basic_thumbnails_settings',
			$this->object->get_static_url('photocrati-nextgen_basic_gallery#thumbnails/nextgen_basic_thumbnails_settings.js'),
			array('jquery.nextgen_radio_toggle')
		);
			$atp = $this->object->get_registry()->get_utility('I_Attach_To_Post_Controller');
	
		if ($atp != null) {
			$atp->mark_script('nextgen_basic_thumbnails_settings');		}
	}

	/**
     * Returns a list of fields to render on the settings page
     */
    function _get_field_names()
    {
        return array(
            'thumbnail_override_settings',
            'nextgen_basic_thumbnails_images_per_page',
            'nextgen_basic_thumbnails_number_of_columns',
            'nextgen_basic_thumbnails_ajax_pagination',
            'nextgen_basic_thumbnails_hidden',
            'nextgen_basic_thumbnails_imagebrowser_effect',
            'nextgen_basic_thumbnails_show_piclens_link',
            'nextgen_basic_thumbnails_piclens_link_text',
            'nextgen_basic_thumbnails_show_slideshow_link',
            'nextgen_basic_thumbnails_slideshow_link_text',
            'nextgen_basic_templates_template',
        );
    }

    /**
     * Renders the images_per_page settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_images_per_page_field($display_type)
    {
        return $this->_render_number_field(
            $display_type,
            'images_per_page',
            'Images per page',
            $display_type->settings['images_per_page'],
            '"0" will display all images at once',
            FALSE,
            '# of images',
            0
        );
    }

    /**
     * Renders the number_of_columns settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_number_of_columns_field($display_type)
    {
        return $this->_render_number_field(
            $display_type,
            'number_of_columns',
            'Number of columns to display',
            $display_type->settings['number_of_columns'],
            '',
            FALSE,
            '# of columns',
            0
        );
    }

    /**
     * Renders the piclens_link_text settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_piclens_link_text_field($display_type)
    {
        return $this->_render_text_field(
            $display_type,
            'piclens_link_text',
            'Piclens link text',
            $display_type->settings['piclens_link_text'],
            '',
            !empty($display_type->settings['show_piclens_link']) ? FALSE : TRUE
        );
    }

    /**
     * Renders the show_piclens_link settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_show_piclens_link_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'show_piclens_link',
            'Show piclens link',
            $display_type->settings['show_piclens_link']
        );
    }

    /**
     * Renders the show_piclens_link settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_hidden_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'show_all_in_lightbox',
            'Add Hidden Images',
            $display_type->settings['show_all_in_lightbox'],
            'If pagination is used this option will show all images in the modal window (Thickbox, Lightbox etc.) This increases page load.'
        );
    }

    function _render_nextgen_basic_thumbnails_imagebrowser_effect_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'use_imagebrowser_effect',
            'Use imagebrowser effect',
            $display_type->settings['use_imagebrowser_effect'],
            'When active each image in the gallery will link to an imagebrowser display and lightbox effects will not be applied.'
        );
    }

    /**
     * Renders the show_piclens_link settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_ajax_pagination_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'ajax_pagination',
            'Enable AJAX pagination',
            $display_type->settings['ajax_pagination'],
            'Browse images without reloading the page.'
        );
    }

    /**
     * Renders the show_slideshow_link settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_show_slideshow_link_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'show_slideshow_link',
            'Show slideshow link',
            $display_type->settings['show_slideshow_link']
        );
    }

    /**
     * Renders the slideshow_link_text settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_thumbnails_slideshow_link_text_field($display_type)
    {
        return $this->_render_text_field(
            $display_type,
            'slideshow_link_text',
            'Slideshow link text',
            $display_type->settings['slideshow_link_text'],
            '',
            !empty($display_type->settings['show_slideshow_link']) ? FALSE : TRUE
        );
    }
}