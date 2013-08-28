<?php

class C_Mvc_Option_Handler
{
	function get($option, $default=NULL)
	{
		return path_join(dirname(__FILE__), 'templates');
	}
}