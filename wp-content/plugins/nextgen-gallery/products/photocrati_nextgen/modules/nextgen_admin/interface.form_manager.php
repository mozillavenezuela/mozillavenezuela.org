<?php

interface I_Form_Manager
{
	function add_form($type, $interfaces);
	function remove_form($type, $interfaces);
	function add_form_before($type, $before, $interfaces);
	function add_form_after($type, $after, $interfaces);
	function get_forms($type);
	function get_known_types();
}