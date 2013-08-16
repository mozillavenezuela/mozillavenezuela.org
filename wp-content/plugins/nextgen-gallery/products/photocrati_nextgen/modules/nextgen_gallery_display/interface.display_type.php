<?php

interface I_Display_Type extends I_DataMapper_Model
{
	function is_compatible_with_source($source);
}