<?php

interface I_DataMapper_Model
{
	function save($attributes=array());
	function destroy();
	function update_attributes();
	function is_new();
}