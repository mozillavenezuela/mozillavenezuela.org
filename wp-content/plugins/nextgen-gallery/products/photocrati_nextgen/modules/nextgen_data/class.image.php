<?php


class Mixin_NextGen_Gallery_Image_Validation extends Mixin
{
	function validation()
	{
		$this->validates_presence_of('galleryid', 'filename', 'alttext', 'exclude', 'sortorder', 'imagedate');
        $this->validates_numericality_of('galleryid');
        $this->validates_numericality_of($this->id());
		$this->validates_numericality_of('sortorder');

		return $this->object->is_valid();
	}
}

/**
 * Model for NextGen Gallery Images
 */
class C_Image extends C_DataMapper_Model
{
	var $_mapper_interface = 'I_Image_Mapper';

    function define($properties=array(), $mapper=FALSE, $context=FALSE)
    {
        parent::define($mapper, $properties, $context);
		$this->add_mixin('Mixin_NextGen_Gallery_Image_Validation');
        $this->implement('I_Image');
    }

	/**
	 * Instantiates a new model
	 * @param array|stdClass $properties
	 * @param C_DataMapper $mapper
	 * @param string $context
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

	/**
	 * Returns the model representing the gallery associated with this image
	 * @return C_Gallery|stdClass
	 */
    function get_gallery($model=FALSE)
    {
		$gallery_mapper = $this->get_registry()->get_utility('I_Gallery_Mapper');
        return $gallery_mapper->find($this->galleryid, $model);
    }
}