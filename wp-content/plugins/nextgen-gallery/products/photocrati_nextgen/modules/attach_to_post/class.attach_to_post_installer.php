<?php

class C_Attach_To_Post_Installer
{
	function install()
	{
		// Delete cached values. Needed for 2.0.7 and less
		$settings = C_NextGen_Settings::get_instance();
		$settings->delete('attach_to_post_url');
		$settings->delete('gallery_preview_url');
		$settings->delete('attach_to_post_display_tab_js_url');
	}
}