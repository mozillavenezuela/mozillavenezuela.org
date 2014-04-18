<?php

/**
 * Provides an entity for Lightbox Libraries.
 *
 * Properties:
 * - name
 * - code
 * - css_stylesheets
 * - scripts
 */
class C_Lightbox_Library extends C_DataMapper_Model
{
	function define($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		parent::define($mapper, $properties, $context);
		$this->add_mixin('Mixin_Lightbox_Library_Validation');
		$this->implement('I_Lightbox_Library');
	}

	function initialize($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		// Get the mapper is not specified
		if (!$mapper) {
			$mapper = $this->get_registry()->get_utility($this->_mapper_interface);
		}

		// Initialize
		parent::initialize($mapper, $properties);
	}
}

class Mixin_Lightbox_Library_Validation extends Mixin
{
	function validation()
	{
		$this->object->validates_presence_of('name');
		$this->object->validates_uniqueness_of('name');

		return $this->object->is_valid();
	}
}