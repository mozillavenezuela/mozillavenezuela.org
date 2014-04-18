<?php

class A_NextGen_Basic_Gallery_Forms extends Mixin
{
    function initialize()
    {
        $this->add_form(NGG_DISPLAY_SETTINGS_SLUG, NGG_BASIC_THUMBNAILS);
        $this->add_form(NGG_DISPLAY_SETTINGS_SLUG, NGG_BASIC_SLIDESHOW);
    }
}