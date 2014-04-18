<?php

class A_Gallery_Display_Ajax extends Mixin
{
    public $_run_count = 0;

	function render_displayed_gallery_action()
	{
		$retval = array();

        // this must run ONLY twice
		if (isset($_POST['ajax_referrer']) && $this->_run_count <= 1)
        {
            // set the router & routed app to use the uri provided in ajax_referrer
            $parsed_url = parse_url($_POST['ajax_referrer']);
            $url = $parsed_url['path'];
            if (!empty($parsed_url['query']))
                $url .= '?' . $parsed_url['query'];

			$_SERVER['REQUEST_URI'] = $url;
            $_SERVER['PATH_INFO'] = $parsed_url['path'];
            $this->_run_count++;
            $router = C_Router::get_instance();
            $router->get_routed_app()
                   ->set_app_request_uri($_SERVER['REQUEST_URI']);
            $router->serve_request();
		}

		if (isset($_POST['displayed_gallery_id'])) {
			$displayed_gallery = new C_Displayed_Gallery();
			$displayed_gallery->apply_transient($_POST['displayed_gallery_id']);
			$renderer = C_Displayed_Gallery_Renderer::get_instance();
			$retval['html'] = $renderer->render($displayed_gallery, TRUE);
		}

		return $retval;
	}
}

