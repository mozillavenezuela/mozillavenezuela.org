<?php

class C_Ajax_Option_Handler
{
	private $slug = 'photocrati_ajax';

	function get_router()
	{
		return C_Component_Registry::get_instance()->get_utility('I_Router');
	}

	function get($key, $default=NULL)
	{
		$retval = $default;

		switch($key) {
			case 'ajax_slug':
				$retval = $this->slug;
				break;
			case 'ajax_url':
				$retval = $this->get_router()->get_url($this->slug, FALSE);
				break;
			case 'ajax_js_url':
				$retval = $this->get_router()->get_static_url('photocrati-ajax#ajax.js');
				break;
		}
		return $retval;
	}
}