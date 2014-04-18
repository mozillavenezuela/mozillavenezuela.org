<?php

class A_NextGen_Basic_SinglePic_Forms extends Mixin
{
    function initialize()
    {
        $this->add_form(
            NGG_DISPLAY_SETTINGS_SLUG, NGG_BASIC_SINGLEPIC
        );
    }
}