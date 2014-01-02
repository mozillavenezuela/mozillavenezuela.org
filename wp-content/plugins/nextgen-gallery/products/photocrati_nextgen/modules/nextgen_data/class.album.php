<?php

class C_Album extends C_DataMapper_Model
{
    var $_mapper_interface = 'I_Album_Mapper';


    function define($properties=array(), $mapper=FALSE, $context=FALSE)
    {
        parent::define($mapper, $properties, $context);
        $this->add_mixin('Mixin_NextGen_Album_Instance_Methods');
        $this->implement('I_Album');
    }


    /**
     * Instantiates an Album object
     * @param bool|\C_DataMapper|\FALSE $mapper
     * @param array $properties
     */
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

/**
 * Provides instance methods for the album
 */
class Mixin_NextGen_Album_Instance_Methods extends Mixin
{
    function validation()
    {
        $this->validates_presence_of('name');
        $this->validates_numericality_of('previewpic');
        return $this->object->is_valid();
    }

    /**
     * Gets all galleries associated with the album
     */
    function get_galleries($models=FALSE)
    {
        $retval = array();
        $mapper = $this->object->get_registry()->get_utility('I_Gallery_Mapper');
        $gallery_key = $mapper->get_primary_key_column();
        $retval = $mapper->find_all(array("{$gallery_key} IN %s", $this->object->sortorder), $models);
        return $retval;
    }
}