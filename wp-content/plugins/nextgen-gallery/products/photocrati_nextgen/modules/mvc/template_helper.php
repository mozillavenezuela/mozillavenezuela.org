<?php

// These functions do NOT work when the Adminer plugin is installed, and being
// viewed. As there's no need to use these functions when viewing Adminer, we'll
// just skip this
if (strpos($_SERVER['REQUEST_URI'], 'adminer') === FALSE) {

    if (!function_exists('h')) {
        function h($str)
        {
            return str_replace("'", "&#39;", htmlentities($str));
        }
    }

    if (!function_exists('echo_h')) {
        function echo_h($str)
        {
            echo h($str);
        }
    }
}