<?php
/**
 * Properties:
 * - title
 * - name
 * - returns
 */
class C_Displayed_Gallery_Source extends C_DataMapper_Model
{
	var $_mapper_interface = 'I_Displayed_Gallery_Source_Mapper';

	function define($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		parent::define($mapper, $properties, $context);
		$this->add_mixin('Mixin_Displayed_Gallery_Source');
		$this->implement('I_Displayed_Gallery_Source');
	}


	/**
	 * Creates an instance of a displayed gallery source
	 * @param type $mapper
	 * @param type $properties
	 */
	function initialize($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		// If no mapper was specified, then get the mapper
		if (!$mapper) $mapper = $this->get_registry()->get_utility($this->_mapper_interface);

		// Construct the model
		parent::initialize($mapper, $properties);
	}
}

class Mixin_Displayed_Gallery_Source extends Mixin
{
	/**
	 * Validates the object
	 * @return bool
	 */
	function validation()
	{
		$this->object->validates_presence_of('title');
		$this->object->validates_presence_of('name');
		$this->object->validates_presence_of('returns');
		$this->object->validates_uniqueness_of('name');
		return $this->object->is_valid();
	}
}