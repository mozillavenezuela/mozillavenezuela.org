<?php

interface I_MVC_Controller
{
    function set_content_type($type);

    function render_view($__name, $__args);

    function render_partial($__name, $__args, $__return);

    function http_error($message, $code);
}