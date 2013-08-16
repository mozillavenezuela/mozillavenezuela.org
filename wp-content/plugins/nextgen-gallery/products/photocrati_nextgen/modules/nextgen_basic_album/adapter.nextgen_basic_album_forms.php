<?php

class A_NextGen_Basic_Album_Forms extends Mixin
{
    function initialize()
    {
        $this->add_form(
            NEXTGEN_DISPLAY_SETTINGS_SLUG,
            NEXTGEN_GALLERY_NEXTGEN_BASIC_COMPACT_ALBUM
        );
        $this->add_form(
            NEXTGEN_DISPLAY_SETTINGS_SLUG,
            NEXTGEN_GALLERY_NEXTGEN_BASIC_EXTENDED_ALBUM
        );
    }
}