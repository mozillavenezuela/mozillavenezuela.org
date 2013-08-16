<?php

interface I_Displayed_Gallery_Source_Mapper
{
	/**
	 * Provides a means to find a displayed gallery source with a particular name
	 * @param string $name
	 */
	function find_by_name($name);
}