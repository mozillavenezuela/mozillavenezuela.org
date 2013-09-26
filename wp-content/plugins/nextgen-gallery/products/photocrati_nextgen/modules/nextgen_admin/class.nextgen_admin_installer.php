<?php

class C_NextGen_Admin_Installer
{
	function install()
	{
		// In version 0.2 of this module and earlier, the following values
		// were statically set rather than dynamically using a handler. Therefore, we need
		// to delete those static values
		$module_name = 'photocrati-nextgen_admin';
		$settings = C_NextGen_Settings::get_instance();
		$modules = $settings->pope_module_list;
		$cleanup = FALSE;
		if (!isset($modules[$module_name])) $cleanup = FALSE;
		elseif (floatval(str_replace($module_name, '|', $modules[$module_name])) < '0.3') {
			$cleanup = TRUE;
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
