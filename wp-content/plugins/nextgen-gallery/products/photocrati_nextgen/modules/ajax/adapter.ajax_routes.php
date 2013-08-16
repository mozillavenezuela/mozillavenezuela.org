<?php

class A_Ajax_Routes extends Mixin
{
	function initialize()
	{
		// We need to add the route after the router has been fully instantiated
		$this->object->add_pre_hook(
			'serve_request',
			get_class(),
			get_class(),
			'add_ajax_routes'
		);
	}

	function add_ajax_routes()
	{
		$app = $this->object->create_app('/photocrati_ajax');
		$app->route('/', 'I_Ajax_Controller#index');
	}
}