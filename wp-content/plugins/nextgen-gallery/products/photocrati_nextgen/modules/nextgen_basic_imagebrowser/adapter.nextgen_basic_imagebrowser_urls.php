<?php

class A_NextGen_Basic_ImageBrowser_Urls extends Mixin
{
	function create_parameter_segment($key, $value, $id=NULL, $use_prefix=FALSE)
	{
		if ($key == 'pid')
			return "image/{$value}";
		else
			return $this->call_parent('create_parameter_segment', $key, $value, $id, $use_prefix);
	}
}