<?php

class A_Routing_App_Factory extends Mixin
{
    function routing_app($context = FALSE, $router = FALSE)
    {
        return new C_Routing_App($context, $router);
    }
}
