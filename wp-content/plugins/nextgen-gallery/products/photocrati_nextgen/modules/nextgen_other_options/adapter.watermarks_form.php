<?php

class A_Watermarks_Form extends Mixin
{
	function get_model()
	{
		return C_Settings_Model::get_instance();
	}

	function get_title()
	{
		return 'Watermarks';
	}

	/**
	 * Gets all fonts installed for watermarking
	 * @return array
	 */
	function _get_watermark_fonts()
	{
		$retval = array();
		foreach (scandir(path_join(NGGALLERY_ABSPATH, 'fonts')) as $filename) {
			if (strpos($filename, '.') === 0) continue;
			else $retval[] = $filename;
		}
		return $retval;
	}

	/**
	 * Gets watermark sources, along with their respective fields
	 * @return array
	 */
	function _get_watermark_sources()
	{
		// We do this so that an adapter can add new sources
		return array(
			'Using an Image'	=>	'image',
			'Using Text'		=>	'text',
		);
	}

	/**
	 * Renders the fields for a watermark source (image, text)
	 * @return string
	 */
	function _get_watermark_source_fields()
	{
		$retval = array();
		foreach ($this->object->_get_watermark_sources() as $label => $value) {
			$method = "_render_watermark_{$value}_fields";
            if ($this->object->has_method($method)) {
                $retval[$value] = $this->object->call_method($method);
            }
		}
		return $retval;
	}

	/**
	 * Render fields that are needed when 'image' is selected as a watermark
	 * source
	 * @return string
	 */
	function _render_watermark_image_fields()
	{
		return $this->object->render_partial('photocrati-nextgen_other_options#watermark_image_fields', array(
			'image_url_label'			=>	_('Image URL:'),
			'watermark_image_url'		=>	$this->object->get_model()->wmPath,
		), TRUE);
	}

	/**
	 * Render fields that are needed when 'text is selected as a watermark
	 * source
	 * @return string
	 */
	function _render_watermark_text_fields()
	{
		$settings = $this->object->get_model();
		return $this->object->render_partial('photocrati-nextgen_other_options#watermark_text_fields', array(
			'fonts'						=>	$this->object->_get_watermark_fonts($settings),
			'font_family_label'			=>	_('Font Family:'),
			'font_family'				=>	$settings->wmFont,
			'font_size_label'			=>	_('Font Size:'),
			'font_size'					=>	$settings->wmSize,
			'font_color_label'			=>	_('Font Color:'),
			'font_color'				=>	strpos($settings->wmColor, '#') === 0 ?
											$settings->wmColor : "#{$settings->wmColor}",
			'watermark_text_label'		=>	_('Text:'),
			'watermark_text'			=>	$settings->wmText,
			'opacity_label'				=>	_('Opacity:'),
			'opacity'					=>	$settings->wmOpaque,
		), TRUE);
	}

	function render()
	{
		$settings	= $this->get_model();
		$registry	= $this->object->get_registry();
		$storage	= $registry->get_utility('I_Gallery_Storage');
		$image		= $registry->get_utility('I_Image_Mapper')->find_first();
		$imagegen	= $registry->get_utility('I_Dynamic_Thumbnails_Manager');
		$size		= $imagegen->get_size_name(array(
			'height'	=>	250,
			'crop'		=>	FALSE,
			'watermark'	=>	TRUE
		));
		$thumb_url	= $image ? $storage->get_image_url($image, $size) : NULL;

		return $this->render_partial('photocrati-nextgen_other_options#watermarks_tab', array(
			'notice'					=>	_('Please note : You can only activate the watermark under -> Manage Gallery . This action cannot be undone.'),
			'watermark_source_label'	=>	_('How will you generate a watermark?'),
			'watermark_sources'			=>	$this->object->_get_watermark_sources(),
			'watermark_fields'			=>	$this->object->_get_watermark_source_fields($settings),
			'watermark_source'			=>	$settings->wmType,
			'position_label'			=>	_('Position:'),
			'position'					=>	$settings->wmPos,
			'offset_label'				=>	_('Offset:'),
			'offset_x'					=>	$settings->wmXpos,
			'offset_y'					=>	$settings->wmYpos,
			'hidden_label'				=>	_('(Show Customization Options)'),
			'active_label'				=>	_('(Hide Customization Options)'),
            'thumbnail_url'             => $thumb_url,
            'preview_label'             => _('Preview of saved settings:'),
            'refresh_label'             => _('Refresh preview image'),
            'refresh_url'               => $settings->ajax_url
		), TRUE);
	}

	function save_action()
	{
		if (($settings = $this->object->param('watermark_options'))) {
			$this->object->get_model()->set($settings)->save();
		}
	}
}
