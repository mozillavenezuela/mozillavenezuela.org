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
		$slug	= '/'.C_NextGen_Settings::get_instance()->router_param_slug;

		// If we're viewing an album, rewrite the urls
		$regex = "/photocrati-nextgen_basic_\\w+_album/";
        if (preg_match($regex, $display_type)) {
            $app->rewrite("{*}{$slug}/page/{\\d}{*}",		 "{1}{$slug}/nggpage--{2}{3}", FALSE, TRUE);
            $app->rewrite("{*}{$slug}/pid--{*}",		     "{1}{$slug}/pid--{2}", FALSE, TRUE); // avoid conflicts with imagebrowser
            $app->rewrite("{*}{$slug}/{\\w}/{\\w}/{\\w}{*}", "{1}{$slug}/album--{2}/gallery--{3}/{4}{5}", FALSE, TRUE);
            $app->rewrite("{*}{$slug}/{\\w}/{\\w}",          "{1}{$slug}/album--{2}/gallery--{3}", FALSE, TRUE);

            // TODO: We're commenting this out as it was causing a problem with sub-album requests not
            // working when placed beside paginated galleries. But we still need to figure out why, and fix that
            // $app->rewrite("{*}{$slug}/{\\w}", "{1}{$slug}/album--{2}", FALSE, TRUE);
        }
		elseif (preg_match($regex, $original_display_type)) {
			$app->rewrite("{*}{$slug}/album--{\\w}",                    "{1}{$slug}/{2}");
			$app->rewrite("{*}{$slug}/album--{\\w}/gallery--{\\w}",     "{1}{$slug}/{2}/{3}");
			$app->rewrite("{*}{$slug}/album--{\\w}/gallery--{\\w}/{*}", "{1}{$slug}/{2}/{3}/{4}");
		}

		// Perform rewrites
		$app->do_rewrites();
	}
}
