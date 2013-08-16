<?php

class A_NextGen_Basic_Album_Urls extends Mixin
{
    function create_parameter_segment($key, $value, $id=NULL, $use_prefix=FALSE)
    {
        if ($key == 'page') {
            return 'page/'.$value;
        }
        else
            return $this->call_parent('create_parameter_segment', $key, $value, $id, $use_prefix);
    }
}