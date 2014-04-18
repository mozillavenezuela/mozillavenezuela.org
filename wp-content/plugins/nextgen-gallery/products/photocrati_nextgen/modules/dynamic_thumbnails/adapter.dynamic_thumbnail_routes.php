<?php

class A_Dynamic_Thumbnail_Routes extends Mixin
{
	function initialize()
	{
		$this->object->add_pre_hook(
			'serve_request',
			'Adds Dynamic Thumbnail routes',
			get_class(),
			'add_dynamic_thumbnail_routes'
		);
	}

	function add_dynamic_thumbnail_routes()
	{
        $app = $this->create_app('/nextgen-image');

		// The C_Dynamic_Thumbnails Controller was created before the new
		// router implementation was conceptualized. It uses it's own mechanism
		// to parse the REQUEST_URI. It should be refactored to use the router's
		// parameter mechanism, but for now - we'll just removed the segments
		// from the router's visibility, and let the Dynamic Thumbnails Controller
		// do it's own parsing
		$app->rewrite('/{*}', '/');
        $app->route('/', 'I_Dynamic_Thumbnails_Controller#index');
	}
}