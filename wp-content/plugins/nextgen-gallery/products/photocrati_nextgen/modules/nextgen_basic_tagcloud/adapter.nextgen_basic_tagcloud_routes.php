<?php

class A_NextGen_Basic_TagCloud_Routes extends Mixin
{
	function initialize()
	{
		$this->object->add_pre_hook(
			'serve_request',
			'Adds Routes for NextGen Basic TagCloud',
			get_class(),
			'_add_nextgen_basic_tagcloud_routes'
		);
	}

	function _add_nextgen_basic_tagcloud_routes()
	{
		$slug = '/'.C_NextGen_Settings::get_instance()->router_param_slug;
        $this->object->rewrite("{*}{$slug}{*}/tags/{\\w}{*}", "{1}{$slug}{2}/gallerytag--{3}{4}");
	}
}