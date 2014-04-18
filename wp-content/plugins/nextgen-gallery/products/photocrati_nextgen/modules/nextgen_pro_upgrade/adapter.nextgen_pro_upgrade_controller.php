<?php

class A_NextGen_Pro_Upgrade_Controller extends Mixin
{
    function enqueue_backend_resources()
    {
        $this->call_parent('enqueue_backend_resources');
        wp_enqueue_style(
            'nextgen_pro_upgrade_page',
            $this->get_static_url('photocrati-nextgen_pro_upgrade#style.css')
        );
    }

    function get_page_title()
    {
        return 'Upgrade to Pro';
    }

    function get_required_permission()
    {
        return 'NextGEN Change options';
    }

    function index_action()
    {
		$key = C_Photocrati_Cache::generate_key('nextgen_pro_upgrade_page');
		if (($html = C_Photocrati_Cache::get('nextgen_pro_upgrade_page', FALSE))) {
			echo $html;
		}
		else {
			// Get page content
			$params = array(
				'btn_url' => $this->object->get_router()->get_static_url('photocrati-nextgen_pro_upgrade#button.png'),
				'img_url' => $this->object->get_router()->get_static_url('photocrati-nextgen_pro_upgrade#proupgrade.gif')
			);
			$html = $this->render_view('photocrati-nextgen_pro_upgrade#index', $params, TRUE);

			// Cache it
			C_Photocrati_Cache::set($key, $html);

			// Render it
			echo $html;
		}
    }
}
