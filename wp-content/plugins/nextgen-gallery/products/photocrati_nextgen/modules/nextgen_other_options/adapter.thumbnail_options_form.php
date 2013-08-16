<?php

class A_Thumbnail_Options_Form extends Mixin
{
	function get_model()
	{
		return C_Settings_Model::get_instance();
	}

	function get_title()
	{
		return 'Thumbnail Options';
	}

	function render()
	{
		$settings = $this->object->get_model();
		
		return $this->render_partial('photocrati-nextgen_other_options#thumbnail_options_tab', array(
			'thumbnail_dimensions_label'		=>	_('Default thumbnail dimensions:'),
			'thumbnail_dimensions_help'		=>	_('When generating thumbnails, what image dimensions do you desire?'),
			'thumbnail_dimensions_width'		=>	$settings->thumbwidth,
			'thumbnail_dimensions_height'		=>	$settings->thumbheight,
			'thumbnail_crop_label'		=>	_('Set fix dimension?'),
			'thumbnail_crop_help'		=>	_('Ignore the aspect ratio, no portrait thumbnails?'),
			'thumbnail_crop'				=>	$settings->thumbfix,
			'thumbnail_quality_label'		=>	_('Adjust Thumbnail Quality?'),
			'thumbnail_quality_help'		=>	_('When generating thumbnails, what image quality do you desire?'),
			'thumbnail_quality'				=>	$settings->thumbquality,
			'size_list_label'		=>	_('Size List'),
			'size_list_help'		=>	_('List of default sizes used for thumbnails and images'),
			'size_list'		=>	$settings->thumbnail_dimensions,
		), TRUE);
	}

	function save_action()
	{
		if (($settings = $this->object->param('thumbnail_settings'))) {
			$this->object->get_model()->set($settings)->save();
		}
	}
}
