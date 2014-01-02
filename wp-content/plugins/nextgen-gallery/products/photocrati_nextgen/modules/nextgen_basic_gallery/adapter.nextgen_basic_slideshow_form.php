<?php

/**
 * Provides the display settings form for the NextGen Basic Slideshow
 */
class A_NextGen_Basic_Slideshow_Form extends Mixin_Display_Type_Form
{
	function get_display_type_name()
	{
		return NEXTGEN_GALLERY_BASIC_SLIDESHOW;
	}

    function enqueue_static_resources()
    {
        wp_enqueue_script(
            'nextgen_basic_slideshow_settings-js',
            $this->get_static_url('photocrati-nextgen_basic_gallery#slideshow/nextgen_basic_slideshow_settings.js'),
            array('jquery.nextgen_radio_toggle')
        );
		$atp = $this->object->get_registry()->get_utility('I_Attach_To_Post_Controller');
	
	if ($atp != null) {
		$atp->mark_script('nextgen_basic_slideshow_settings-js');	}
    }

    /**
     * Returns a list of fields to render on the settings page
     */
    function _get_field_names()
    {
        return array(
            'nextgen_basic_slideshow_gallery_dimensions',
            'nextgen_basic_slideshow_cycle_effect',
            'nextgen_basic_slideshow_cycle_interval',
            'nextgen_basic_slideshow_images_per_page',
            'nextgen_basic_slideshow_flash_enabled',
            'nextgen_basic_slideshow_flash_background_music',
            'nextgen_basic_slideshow_flash_stretch_image',
            'nextgen_basic_slideshow_flash_transition_effect',
            'nextgen_basic_slideshow_flash_shuffle',
            'nextgen_basic_slideshow_flash_next_on_click',
            'nextgen_basic_slideshow_flash_navigation_bar',
            'nextgen_basic_slideshow_flash_loading_icon',
            'nextgen_basic_slideshow_flash_watermark_logo',
            'nextgen_basic_slideshow_flash_slow_zoom',
            'nextgen_basic_slideshow_flash_xhtml_validation',
            'nextgen_basic_slideshow_flash_background_color',
            'nextgen_basic_slideshow_flash_text_color',
            'nextgen_basic_slideshow_flash_rollover_color',
            'nextgen_basic_slideshow_flash_screen_color',
            'nextgen_basic_slideshow_show_thumbnail_link',
            'nextgen_basic_slideshow_thumbnail_link_text'
        );
    }

    function _render_nextgen_basic_slideshow_cycle_interval_field($display_type)
    {
        return $this->_render_number_field(
            $display_type,
            'cycle_interval',
            'Interval',
            $display_type->settings['cycle_interval'],
            '',
            FALSE,
            '# of seconds',
            1
        );
    }

    function _render_nextgen_basic_slideshow_images_per_page_field($display_type)
    {
        return $this->_render_number_field(
            $display_type,
            'images_per_page',
            'Image limit',
            $display_type->settings['images_per_page'],
            'Maximum number of images to display with recent or random sources',
            FALSE,
            '# of images',
            0
        );
    }

    function _render_nextgen_basic_slideshow_cycle_effect_field($display_type)
    {
        return $this->_render_select_field(
            $display_type,
            'cycle_effect',
            'Effect',
			array(
			'fade' => 'fade',
			'blindX' => 'blindX',
			'cover' => 'cover',
			'scrollUp' => 'scrollUp',
			'scrollDown' => 'scrollDown',
			'shuffle' => 'shuffle',
			'toss' => 'toss',
			'wipe' => 'wipe'
			),
            $display_type->settings['cycle_effect'],
            '',
            FALSE
        );
    }

    function _render_nextgen_basic_slideshow_gallery_dimensions_field($display_type)
    {
        return $this->render_partial('photocrati-nextgen_basic_gallery#slideshow/nextgen_basic_slideshow_settings_gallery_dimensions', array(
            'display_type_name' => $display_type->name,
            'gallery_dimensions_label' => _('Maximum dimensions'),
            'gallery_width' => $display_type->settings['gallery_width'],
            'gallery_height' => $display_type->settings['gallery_height'],
        ), True);
    }

    function _render_nextgen_basic_slideshow_flash_enabled_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'flash_enabled',
            'Enable flash slideshow',
            $display_type->settings['flash_enabled'],
            'Integrate the flash based slideshow for all flash supported devices'
        );
    }

    function _render_nextgen_basic_slideshow_flash_shuffle_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'flash_shuffle',
            'Shuffle',
            $display_type->settings['flash_shuffle'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_next_on_click_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'flash_next_on_click',
            'Show next image on click',
            $display_type->settings['flash_next_on_click'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_navigation_bar_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'flash_navigation_bar',
            'Show navigation bar',
            $display_type->settings['flash_navigation_bar'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_loading_icon_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'flash_loading_icon',
            'Show loading icon',
            $display_type->settings['flash_loading_icon'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_watermark_logo_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'flash_watermark_logo',
            'Use watermark logo',
            $display_type->settings['flash_watermark_logo'],
            'Use the watermark image in the Flash object. Note: this does not watermark the image itself, and cannot be applied with text watermarks',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_stretch_image_field($display_type)
    {
        return $this->_render_select_field(
            $display_type,
            'flash_stretch_image',
            'Stretch image',
			array('true' => 'true', 'false' => 'false', 'fit' => 'fit', 'none' => 'none'),
            $display_type->settings['flash_stretch_image'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_transition_effect_field($display_type)
    {
        return $this->_render_select_field(
            $display_type,
            'flash_transition_effect',
            'Transition / fade effect',
            array(
                'fade' => 'fade',
                'bgfade' => 'bgfade',
                'slowfade' => 'slowfade',
                'circles' => 'circles',
                'bubbles' => 'bubbles',
                'blocks' => 'blocks',
                'fluids' => 'fluids',
                'flash' => 'flash',
                'lines' => 'lines',
                'random' => 'random'
            ),
            $display_type->settings['flash_transition_effect'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_slow_zoom_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'flash_slow_zoom',
            'Use slow zooming effect',
            $display_type->settings['flash_slow_zoom'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_background_music_field($display_type)
    {
        return $this->_render_text_field(
            $display_type,
            'flash_background_music',
            'Background music (url)',
            $display_type->settings['flash_background_music'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE,
            'http://...'
        );
    }

    function _render_nextgen_basic_slideshow_flash_xhtml_validation_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'flash_xhtml_validation',
            'Try XHTML validation',
            $display_type->settings['flash_xhtml_validation'],
            'Uses CDATA. Important: Could cause problems with some older browsers',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_background_color_field($display_type)
    {
        return $this->_render_color_field(
            $display_type,
            'flash_background_color',
            'Background',
            $display_type->settings['flash_background_color'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_text_color_field($display_type)
    {
        return $this->_render_color_field(
            $display_type,
            'flash_text_color',
            'Texts / buttons',
            $display_type->settings['flash_text_color'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_rollover_color_field($display_type)
    {
        return $this->_render_color_field(
            $display_type,
            'flash_rollover_color',
            'Rollover / active',
            $display_type->settings['flash_rollover_color'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    function _render_nextgen_basic_slideshow_flash_screen_color_field($display_type)
    {
        return $this->_render_color_field(
            $display_type,
            'flash_screen_color',
            'Screen',
            $display_type->settings['flash_screen_color'],
            '',
            empty($display_type->settings['flash_enabled']) ? TRUE : FALSE
        );
    }

    /**
     * Renders the show_thumbnail_link settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_slideshow_show_thumbnail_link_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'show_thumbnail_link',
            'Show thumbnail link',
            $display_type->settings['show_thumbnail_link']
        );
    }

    /**
     * Renders the thumbnail_link_text settings field
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_slideshow_thumbnail_link_text_field($display_type)
    {
        return $this->_render_text_field(
            $display_type,
            'thumbnail_link_text',
            'Thumbnail link text',
            $display_type->settings['thumbnail_link_text'],
            '',
            !empty($display_type->settings['show_thumbnail_link']) ? FALSE : TRUE
        );
    }
}
