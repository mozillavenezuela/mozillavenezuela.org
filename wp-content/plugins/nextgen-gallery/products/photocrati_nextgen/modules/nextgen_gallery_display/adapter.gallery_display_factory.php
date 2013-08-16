<?php

class A_Gallery_Display_Factory extends Mixin
{
	/**
	 * Instantiates a Display Type
	 * @param C_DataMapper $mapper
	 * @param array|stdClass|C_DataMapper_Model $properties
	 * @param string|array|FALSE $context
	 */
	function display_type($mapper=FALSE, $properties=array(), $context=FALSE)
	{
		return new C_Display_Type($mapper, $properties, $context);
	}

	/**
	 * Instantiates a Displayed Gallery
	 * @param C_DataMapper $mapper
	 * @param array|stdClass|C_DataMapper_Model $properties
	 * @param string|array|FALSE $context
	 */
	function displayed_gallery($mapper=FALSE, $properties=array(), $context=FALSE)
	{
		return new C_Displayed_Gallery($mapper, $properties, $context);
	}

	/**
	 * Instantiates a Displayed Gallery Source
	 * @param C_DataMapper $mapper
	 * @param array|stdClass|C_DataMapper_Model $properties
	 * @param string|array|FALSE $context
	 * @return C_Displayed_Gallery_Source
	 */
	function displayed_gallery_source($mapper=FALSE, $properties=array(), $context=FALSE)
	{
		return new C_Displayed_Gallery_Source($mapper, $properties, $context);
	}
}