<?php

interface I_Security_Actor
{
	function add_capability($capability_name);
	
	function remove_capability($capability_name);
	
	function is_allowed($capability_name, $args = null);
	
	function is_user();
}
