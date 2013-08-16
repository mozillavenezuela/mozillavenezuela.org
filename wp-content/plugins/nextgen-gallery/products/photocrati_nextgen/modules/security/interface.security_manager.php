<?php

interface I_Security_Manager
{
	function is_allowed($capability_name, $args = null);
	
	function get_actor($actor_id, $actor_type = null, $args = null);
	
	function get_current_actor();
	
	function get_request_token($action_name, $args = null);
}
