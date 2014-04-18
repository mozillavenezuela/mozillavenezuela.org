<?php

/**
 * A Display Type is a component which renders a collection of images
 * in a "gallery".
 *
 * Properties:
 * - entity_types (gallery, album)
 * - name		 (nextgen_basic-thumbnails)
 * - title		 (NextGEN Basic Thumbnails)
 */
class C_Display_Type extends C_DataMapper_Model
{
	var $_mapper_interface = 'I_Display_Type_Mapper';

	function define($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		parent::define($mapper, $properties, $context);
		$this->add_mixin('Mixin_Display_Type_Validation');
		$this->add_mixin('Mixin_Display_Type_Instance_Methods');
		$this->implement('I_Display_Type');
	}

	/**
	 * Initializes a display type with properties
	 * @param FALSE|C_Display_Type_Mapper $mapper
	 * @param array|stdClass|C_Display_Type $properties
	 * @param FALSE|string|array $context
	 */
	function initialize($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		// If no mapper was specified, then get the mapper
		if (!$mapper) $mapper = $this->get_registry()->get_utility($this->_mapper_interface);

		// Construct the model
		parent::initialize($mapper, $properties);
	}


	/**
	 * Allows a setting to be retrieved directly, rather than through the
	 * settings property
	 * @param string $property
	 * @return mixed
	 */
	function &__get($property)
	{
		if (isset($this->object->settings) && isset($this->object->settings[$property])) {
			$retval = &$this->object->settings[$property];
			return $retval;
		}
		else return parent::__get($property);
	}
}

class Mixin_Display_Type_Validation extends Mixin
{
	function validation()
	{
		$this->object->validates_presence_of('entity_types');
		$this->object->validates_presence_of('name');
		$this->object->validates_presence_of('title');

		return $this->object->is_valid();
	}
}

/**
 * Provides methods available for class instances
 */
class Mixin_Display_Type_Instance_Methods extends Mixin
{
	/**
	 * Determines if this display type is compatible with a displayed gallery
	 * source
	 * @param stdClass|C_DataMapper_Model|C_Displayed_Gallery_Source $source
	 * @return bool
	 */
	function is_compatible_with_source($source)
	{
		$success = TRUE;
		foreach ($source->returns as $returned_entity_type) {
			if (!in_array($returned_entity_type, $this->object->entity_types)) {
				$success = FALSE;
				break;
			}
		}

		return $success;
	}
	
	function get_order()
	{
		return NGG_DISPLAY_PRIORITY_BASE;
	}
}
