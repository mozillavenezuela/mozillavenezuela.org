<?php

class C_Attach_To_Post_Option_Handler
{
	function get_router()
	{
		return C_Component_Registry::get_instance()->get_utility('I_Router');
	}

	function get($key, $default=NULL)
	{
		$retval = $default;

		switch ($key) {
			case 'attach_to_post_url':
				$retval = $this->get_router()->get_url('/nextgen-attach_to_post', FALSE);
				break;
			case 'gallery_preview_url':
				$retval = $this->get_router()->get_url('/nextgen-attach_to_post/preview', FALSE);
				break;
			case 'attach_to_post_display_tab_js_url':
				$retval = $this->get_router()->get_url('/nextgen-attach_to_post/display_tab_js', FALSE);
				break;
		}

		return $retval;
	}
}