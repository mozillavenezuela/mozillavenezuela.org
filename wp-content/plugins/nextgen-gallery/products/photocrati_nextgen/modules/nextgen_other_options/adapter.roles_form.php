<?php

class A_Roles_Form extends Mixin
{
	function get_title()
	{
		return 'Roles & Capabilities';
	}

	function render()
	{
		$view = path_join(NGGALLERY_ABSPATH, implode(DIRECTORY_SEPARATOR, array(
			'admin', 'roles.php'
		)));
		include_once ( $view );
		ob_start();
		nggallery_admin_roles();
		$retval = ob_get_contents();
		ob_end_clean();
		return $retval;
	}
}