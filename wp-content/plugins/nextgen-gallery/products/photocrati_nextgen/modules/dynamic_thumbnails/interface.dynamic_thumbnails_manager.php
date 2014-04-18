<?php

interface I_Dynamic_Thumbnails_Manager
{
	function get_route_name();
	
	function get_uri_from_params($params);
	
	function get_image_uri($image, $params);
	
	function get_image_url($image, $params);
	
	function get_params_from_uri($uri);
	
	function get_name_from_params($params, $only_size_name = false, $id_in_name = true);
	
	function get_size_name($params);
	
	function get_image_name($image, $params);
	
	function get_params_from_name($name, $is_only_size_name = false);
	
	function is_size_dynamic($name, $is_only_size_name = false);
}
