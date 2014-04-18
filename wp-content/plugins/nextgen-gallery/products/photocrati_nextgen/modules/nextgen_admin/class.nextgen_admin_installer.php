<?php

class C_NextGen_Admin_Installer
{
	function install()
	{
		// In version 0.2 of this module and earlier, the following values
		// were statically set rather than dynamically using a handler. Therefore, we need
		// to delete those static values
		$module_name = 'photocrati-nextgen_admin';
        $modules = get_option('pope_module_list', array());
        if (!$modules) {
            $settings = C_NextGen_Settings::get_instance();
            $modules = $settings->get('pope_module_list', array());
        }

		$cleanup = FALSE;
        foreach ($modules as $module) {
            if (strpos($module, $module_name) !== FALSE) {
                if (version_compare(array_pop(explode('|', $module)), '0.3') == -1) {
                    $cleanup = TRUE;
                }
                break;
            }
        }

		if ($cleanup) {
			$keys = array(
				'jquery_ui_theme',
				'jquery_ui_theme_version',
				'jquery_ui_theme_url'
			);
			foreach ($keys as $key) $settings->delete($key);
		}
	}
}
