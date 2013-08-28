<?php

// These functions do NOT work when the Adminer plugin is installed, and being
// viewed. As there's no need to use these functions when viewing Adminer, we'll
// just skip this
if (strpos($_SERVER['REQUEST_URI'], 'adminer') === FALSE) {

    if (!function_exists('h')) {
        function h($str)
        {
			if (defined('ENT_HTML401')) {
				return str_replace("'", "&#39;", htmlentities($str, ENT_COMPAT | ENT_HTML401, 'UTF-8'));
			}
			else {
				return str_replace("'", "&#39;", htmlentities($str, ENT_COMPAT, 'UTF-8'));
			}
        }
    }

    if (!function_exists('echo_h')) {
        function echo_h($str)
        {
            echo h($str);
        }
    }
}