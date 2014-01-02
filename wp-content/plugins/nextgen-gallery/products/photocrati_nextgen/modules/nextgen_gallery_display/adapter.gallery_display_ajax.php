<?php

class A_Gallery_Display_Ajax extends Mixin
{
	function render_displayed_gallery_action()
	{
		$retval = array();

		if (isset($_POST['ajax_referrer'])) {
			$_SERVER['REQUEST_URI'] = $_POST['ajax_referrer'];
			C_Router::get_instance()->serve_request();
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

