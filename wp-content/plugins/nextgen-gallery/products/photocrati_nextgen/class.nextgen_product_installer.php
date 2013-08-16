<?php

class C_NextGen_Product_Installer
{
	function uninstall($hard)
	{
		foreach (P_Photocrati_NextGen::$modules as $module_name) {
			if (($handler = C_Photocrati_Installer::get_handler_instance($module_name))) {
				if (method_exists($handler, 'uninstall')) $handler->uninstall($hard);
			}
		}
	}
}