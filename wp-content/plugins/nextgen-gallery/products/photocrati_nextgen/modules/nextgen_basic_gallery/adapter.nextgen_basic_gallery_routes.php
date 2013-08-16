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
		$slug = C_NextGen_Global_Settings::get_instance()->router_param_slug;
        $this->object->rewrite("{$slug}{*}/image/{*}",         "{$slug}{1}/pid--{2}");
        $this->object->rewrite("{$slug}{*}/slideshow/{*}",     "{$slug}{1}/show--" . NEXTGEN_GALLERY_BASIC_SLIDESHOW  . "{2}");
        $this->object->rewrite("{$slug}{*}/thumbnails/{*}",    "{$slug}{1}/show--".  NEXTGEN_GALLERY_BASIC_THUMBNAILS . "{2}");
        $this->object->rewrite("{$slug}{*}/show--slide/{*}",   "{$slug}{1}/show--" . NEXTGEN_GALLERY_BASIC_SLIDESHOW  . "/{2}");
        $this->object->rewrite("{$slug}{*}/show--gallery/{*}", "{$slug}{1}/show--" . NEXTGEN_GALLERY_BASIC_THUMBNAILS . "/{2}");
        $this->object->rewrite("{$slug}{*}/page/{\\d}{*}",     "{$slug}{1}/page--{2}{3}");
    }
}