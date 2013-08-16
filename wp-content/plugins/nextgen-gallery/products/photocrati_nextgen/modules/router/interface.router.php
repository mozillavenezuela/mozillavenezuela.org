<?php

interface I_Router
{
    function serve_request();

    static function get_instance();
}