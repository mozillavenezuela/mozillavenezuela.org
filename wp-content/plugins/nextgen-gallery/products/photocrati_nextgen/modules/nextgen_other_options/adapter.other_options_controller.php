<?php

class A_Other_Options_Controller extends Mixin
{
	function enqueue_backend_resources()
	{
		$this->call_parent('enqueue_backend_resources');
		wp_enqueue_script(
			'nextgen_settings_page',
			$this->get_static_url('photocrati-nextgen_other_options#nextgen_settings_page.js'),
			array('jquery-ui-accordion', 'jquery-ui-tooltip', 'wp-color-picker')
		);

		wp_enqueue_style(
			'nextgen_settings_page',
			$this->get_static_url('photocrati-nextgen_other_options#nextgen_settings_page.css')
		);
	}

	function get_page_title()
	{
		return 'Other Options';
	}
	
	function get_required_permission()
	{
		return 'NextGEN Change options';
	}
}
