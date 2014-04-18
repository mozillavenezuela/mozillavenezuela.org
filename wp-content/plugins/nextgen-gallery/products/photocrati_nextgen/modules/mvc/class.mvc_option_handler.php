<?php

class C_Mvc_Option_Handler
{
	function get($option, $default=NULL)
	{
        return implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'templates'));
	}
}