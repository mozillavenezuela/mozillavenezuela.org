<?php

class A_NextGen_Basic_Album_Urls extends Mixin
{
    function create_parameter_segment($key, $value, $id=NULL, $use_prefix=FALSE)
    {
        if ($key == 'nggpage') {
            return 'page/'.$value;
        }
        elseif ($key == 'album') {
            return $value;
        }
        elseif ($key == 'gallery') {
            return $value;
        }
        else
            return $this->call_parent('create_parameter_segment', $key, $value, $id, $use_prefix);
    }

    function remove_parameter($key, $id=NULL, $url=FALSE)
    {
        $url        = $this->call_parent('remove_parameter', $key, $id, $url);
        $settings	= C_NextGen_Settings::get_instance();
        $param_slug = preg_quote($settings->router_param_slug, '#');

        if (preg_match("#(/{$param_slug}/.*)album--#", $url, $matches)) {
            $url = str_replace($matches[0], $matches[1], $url);
        }

        if (preg_match("#(/{$param_slug}/.*)gallery--#", $url, $matches)) {
            $url = str_replace($matches[0], $matches[1], $url);
        }

        return $url;
    }
}
