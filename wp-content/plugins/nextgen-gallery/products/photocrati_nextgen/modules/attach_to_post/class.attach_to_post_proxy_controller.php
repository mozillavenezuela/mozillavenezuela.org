<?php

/**
 * Work in progress. This won't quite work as expected
 * as we can't cache the Attach to post interface since
 * it needs to be invalided when the displayed gallery changes
 * Class C_Attach_To_Post_Proxy_Controller
 */
class C_Attach_To_Post_Proxy_Controller
{
	static $_instance = NULL;

	static function get_instance()
	{
		if (is_null(self::$_instance)) {
			$klass = get_class();
			self::$_instance = new $klass();
		}
		return self::$_instance;
	}

	function index_action()
	{
		$url = C_Router::get_instance()->get_routed_app()->get_routed_url(TRUE);
		$key = C_Photocrati_Cache::generate_key($url);

		// Try fetching the contents from the cache
		if (($html = C_Photocrati_Cache::get($key, FALSE))) {
			echo $html;
		}
		else {
			$controller = C_Attach_Controller::get_instance(FALSE);
			$html = $controller->index_action(TRUE);
			C_Photocrati_Cache::set($key, $html);
			echo $html;
		}

	}

	function display_tab_js_action()
	{
		$url = C_Router::get_instance()->get_routed_app()->get_routed_url(TRUE);
		$key = C_Photocrati_Cache::generate_key($url);

		// Try fetching the contents from the cache
		if (($html = C_Photocrati_Cache::get($key, FALSE))) {
			echo $html;
		}
		else {
			$html = C_Attach_Controller::get_instance(FALSE)->display_tab_js_action(TRUE);
			C_Photocrati_Cache::set($key, $html);
			echo $html;
		}
	}

	function preview_action()
	{
		return C_Attach_Controller::get_instance(FALSE)->preview_action();
	}

	private function __construct() {}
}