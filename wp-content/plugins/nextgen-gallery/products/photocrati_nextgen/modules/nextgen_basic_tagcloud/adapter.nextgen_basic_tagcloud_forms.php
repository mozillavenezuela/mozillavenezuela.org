<?php

class A_NextGen_Basic_TagCloud_Forms extends Mixin
{
    function initialize()
    {
        $this->add_form(
            NEXTGEN_DISPLAY_SETTINGS_SLUG, NEXTGEN_BASIC_TAG_CLOUD_MODULE_NAME
        );
    }
}