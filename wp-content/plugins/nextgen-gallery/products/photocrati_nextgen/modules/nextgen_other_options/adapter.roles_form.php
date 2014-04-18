<?php

class A_Roles_Form extends Mixin
{
	function get_title()
	{
		return 'Roles & Capabilities';
	}

	function render()
	{
        $view = implode(DIRECTORY_SEPARATOR, array(
            rtrim(NGGALLERY_ABSPATH, "/\\"),
            'admin',
            'roles.php'
        ));
		include_once ( $view );
		ob_start();
		nggallery_admin_roles();
		$retval = ob_get_contents();
		ob_end_clean();
		return $retval;
	}
}