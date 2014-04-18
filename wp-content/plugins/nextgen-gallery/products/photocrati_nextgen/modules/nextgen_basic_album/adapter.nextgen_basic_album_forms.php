<?php

class A_NextGen_Basic_Album_Forms extends Mixin
{
    function initialize()
    {
        $this->add_form(
            NGG_DISPLAY_SETTINGS_SLUG,
            NGG_BASIC_COMPACT_ALBUM
        );
        $this->add_form(
            NGG_DISPLAY_SETTINGS_SLUG,
            NGG_BASIC_EXTENDED_ALBUM
        );
    }
}