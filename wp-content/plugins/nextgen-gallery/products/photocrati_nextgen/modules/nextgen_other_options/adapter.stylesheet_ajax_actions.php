<?php

/**
 * Registers new AJAX functions for retrieving/updating
 * the contents of CSS stylesheets
 */
class A_Stylesheet_Ajax_Actions extends Mixin
{
	/**
	 * Retrieves the contents of the CSS stylesheet specified
	 */
	function get_stylesheet_contents_action()
	{
		$retval = array();

		if ($this->object->_authorized_for_stylesheet_action()) {

			$styles 	= C_NextGen_Style_Manager::get_instance();
			$abspath	= $styles->find_selected_stylesheet_abspath($this->object->param('cssfile'));
			$writepath	= $styles->get_selected_stylesheet_saved_abspath($this->object->param('cssfile'));
			if (is_readable($abspath)) {
				$retval['contents'] = file_get_contents($abspath);
				$retval['writable'] = is_writable($abspath);
				$retval['abspath']  = $abspath;
				$retval['writepath']= $writepath;
			}
			else $retval['error'] = "Could not find stylesheet";
		}
		else {
			$retval['error'] = 'Unauthorized';
		}

		return $retval;

	}


	/**
	 * Determines if the request is authorized
	 * @return boolean
	 */
	function _authorized_for_stylesheet_action()
	{
		$security = $this->get_registry()->get_utility('I_Security_Manager');
		$sec_actor = $security->get_current_actor();
		
		return $sec_actor->is_allowed('nextgen_edit_style');
	}
}
