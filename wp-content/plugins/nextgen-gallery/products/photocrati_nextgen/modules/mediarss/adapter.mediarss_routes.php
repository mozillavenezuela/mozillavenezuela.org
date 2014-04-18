<?php

class A_MediaRSS_Routes extends Mixin
{
	function initialize()
	{
		$this->object->add_pre_hook(
			'serve_request',
			'Adds MediaRSS routes',
			get_class(),
			'add_mediarss_routes'
		);
	}

	function add_mediarss_routes()
	{
		$app = $this->create_app('/nextgen-mediarss');
        $app->route(
            '/',
            array(
                'controller' => 'I_MediaRSS_Controller',
                'action'  => 'index',
                'context' => FALSE
            )
        );
	}
}