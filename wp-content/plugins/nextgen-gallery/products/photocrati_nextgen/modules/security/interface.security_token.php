<?php

interface I_Security_Token
{
	function get_request_list($args = null);
	
	function get_form_html($args = null);
	
	function get_json($args = null);
	
	function check_request($request_values);
	
	function check_current_request();
}
