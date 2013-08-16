<?php

class A_Display_Settings_Controller extends Mixin
{
	/**
	 * Static resources required for the Display Settings page
	 */
	function enqueue_backend_resources()
	{
		$this->call_parent('enqueue_backend_resources');
		wp_enqueue_style('nextgen_gallery_display_settings');
		wp_enqueue_script('nextgen_gallery_display_settings');
	}

	function get_page_title()
	{
		return 'Gallery Settings';
	}
	
	function get_required_permission()
	{
		return 'NextGEN Change options';
	}
}
