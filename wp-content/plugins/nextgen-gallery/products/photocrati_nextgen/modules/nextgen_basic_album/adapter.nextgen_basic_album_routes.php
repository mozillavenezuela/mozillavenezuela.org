<?php

class A_NextGen_Basic_Album_Routes extends Mixin
{
	function initialize()
	{
		$this->object->add_pre_hook(
			'render',
			'Add late url rewriting for albums',
			__CLASS__,
			'_nextgen_basic_album_rewrite_rules'
		);
	}

	function _nextgen_basic_album_rewrite_rules($displayed_gallery)
	{
		// Get display types
		$original_display_type = isset($displayed_gallery->display_settings['original_display_type']) ?
			$displayed_gallery->display_settings['original_display_type'] : '';
		$display_type = $displayed_gallery->display_type;

		// Get router
        $router = $this->get_registry()->get_utility('I_Router');
        $app 	= $router->get_routed_app();
		$slug	= C_NextGen_Settings::get_instance()->router_param_slug;

		// If we're viewing an album, rewrite the urls
		$regex = "/photocrati-nextgen_basic_\w+_album/";
		if (preg_match($regex, $display_type)) {
			$app->rewrite("{$slug}/pid--{*}",		      "{$slug}/pid--{1}", FALSE, TRUE); // avoid conflicts with imagebrowser
			$app->rewrite("{$slug}/{\\w}",                "{$slug}/album--{1}");
			$app->rewrite("{$slug}/{\\w}/{\\w}",          "{$slug}/album--{1}/gallery--{2}");
			$app->rewrite("{$slug}/{\\w}/{\\w}/{\\w}{*}", "{$slug}/album--{1}/gallery--{2}/{3}{4}");
		}
		elseif (preg_match($regex, $original_display_type)) {
			$app->rewrite("{$slug}/album--{\\w}",                    "{$slug}/{1}");
			$app->rewrite("{$slug}/album--{\\w}/gallery--{\\w}",     "{$slug}/{1}/{2}");
			$app->rewrite("{$slug}/album--{\\w}/gallery--{\\w}/{*}", "{$slug}/{1}/{2}/{3}");
		}

		// Perform rewrites
		$app->do_rewrites();
	}
}
