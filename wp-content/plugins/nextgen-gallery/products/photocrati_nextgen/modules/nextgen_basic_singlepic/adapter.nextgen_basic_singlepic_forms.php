<?php

class A_NextGen_Basic_SinglePic_Forms extends Mixin
{
    function initialize()
    {
        $this->add_form(
            NEXTGEN_DISPLAY_SETTINGS_SLUG, NEXTGEN_BASIC_SINGLEPIC_MODULE_NAME
        );
    }
}