<?php

class A_MVC_Factory extends Mixin
{
    function mvc_view($template, $params=array(), $engine='php', $context=FALSE)
    {
        return new C_MVC_View($template, $params, $engine, $context);
    }
}