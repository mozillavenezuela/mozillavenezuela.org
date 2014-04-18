<?php

class C_Ajax_Installer
{
	function install()
	{
		// Delete cached values. Needed for 2.0.7 and less
		$settings = C_NextGen_Settings::get_instance();
		$settings->delete('ajax_url');
		$settings->delete('ajax_slug');
		$settings->delete('ajax_js_url');
	}
}