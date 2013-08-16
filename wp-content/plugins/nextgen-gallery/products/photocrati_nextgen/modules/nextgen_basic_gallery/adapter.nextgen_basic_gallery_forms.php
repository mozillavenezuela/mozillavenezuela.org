<?php

class A_NextGen_Basic_Gallery_Forms extends Mixin
{
    function initialize()
    {
        $this->add_form(NEXTGEN_DISPLAY_SETTINGS_SLUG, NEXTGEN_GALLERY_BASIC_THUMBNAILS);
        $this->add_form(NEXTGEN_DISPLAY_SETTINGS_SLUG, NEXTGEN_GALLERY_BASIC_SLIDESHOW);
    }
}