<?php

class A_MVC_Router extends Mixin
{
	/**
	 * First tries to find the static file in the 'static' folder
	 * @param string $path
	 * @param string $module
	 * @return string
	 */
	function get_static_url($path, $module=FALSE)
	{
		// Determine the base url
		$base_url = $this->object->get_base_url(TRUE);
		$base_url = $this->object->remove_url_segment('/index.php', $base_url);

		// Find the module directory
		$fs = $this->object->get_registry()->get_utility('I_Fs');

		return $fs->join_paths(
			$base_url,
			$fs->find_static_abspath($path, $module, TRUE)
		);
	}
}