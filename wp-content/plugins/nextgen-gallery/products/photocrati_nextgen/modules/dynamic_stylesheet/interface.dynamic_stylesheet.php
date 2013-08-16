<?php

interface I_Dynamic_Stylesheet
{
	function register($name, $template);
	function enqueue($name, $vars);
}