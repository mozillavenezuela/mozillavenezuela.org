<?php

interface I_Form
{
	function render($retval=TRUE);
	function save_action($properties=array());
}