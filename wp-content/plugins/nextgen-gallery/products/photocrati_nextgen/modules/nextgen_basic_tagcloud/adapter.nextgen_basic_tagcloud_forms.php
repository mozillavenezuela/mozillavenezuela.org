<?php

class A_NextGen_Basic_TagCloud_Forms extends Mixin
{
    function initialize()
    {
        $this->add_form(
            NGG_DISPLAY_SETTINGS_SLUG, NGG_BASIC_TAGCLOUD
        );
    }
}