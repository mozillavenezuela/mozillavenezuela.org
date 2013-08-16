<?php

class C_NggLegacy_Installer
{
	function install()
	{
		include_once('admin/install.php');
		nggallery_install();
	}

	function uninstall($hard=FALSE)
	{
		delete_option('ngg_init_check');
		delete_option('ngg_update_exists');
	}
}
