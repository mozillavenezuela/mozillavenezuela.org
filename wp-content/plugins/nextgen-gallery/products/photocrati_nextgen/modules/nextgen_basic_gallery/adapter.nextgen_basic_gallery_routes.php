<?php

class A_NextGen_Basic_Gallery_Routes extends Mixin
{
    function initialize()
    {
        $this->object->add_pre_hook(
            'serve_request',
            get_class(),
            get_class(),
            'add_nextgen_basic_gallery_routes'
        );
    }
    
    function add_nextgen_basic_gallery_routes()
    {
		$slug = '/'.C_NextGen_Settings::get_instance()->router_param_slug;
        $this->object->rewrite("{*}{$slug}{*}/image/{*}",         "{1}{$slug}{2}/pid--{3}");
        $this->object->rewrite("{*}{$slug}{*}/slideshow/{*}",     "{1}{$slug}{2}/show--" . NGG_BASIC_SLIDESHOW  . "{3}");
        $this->object->rewrite("{*}{$slug}{*}/thumbnails/{*}",    "{1}{$slug}{2}/show--".  NGG_BASIC_THUMBNAILS . "{3}");
        $this->object->rewrite("{*}{$slug}{*}/show--slide/{*}",   "{1}{$slug}{2}/show--" . NGG_BASIC_SLIDESHOW  . "/{3}");
        $this->object->rewrite("{*}{$slug}{*}/show--gallery/{*}", "{1}{$slug}{2}/show--" . NGG_BASIC_THUMBNAILS . "/{3}");
        $this->object->rewrite("{*}{$slug}{*}/page/{\\d}{*}",     "{1}{$slug}{2}/nggpage--{3}{4}");
    }
}