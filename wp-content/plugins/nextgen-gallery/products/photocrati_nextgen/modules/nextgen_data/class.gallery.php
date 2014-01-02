<?php

class Mixin_NextGen_Gallery_Validation
{
    /**
     * Validates whether the gallery can be saved
     */
    function validation()
    {
        // If a title is present, we can auto-populate some other properties
        if (($this->object->title)) {

            // If no name is present, use the title to generate one
            if (!($this->object->name)) {
                $this->object->name = sanitize_file_name( sanitize_title($this->object->title));
                $this->object->name = apply_filters('ngg_gallery_name', $this->object->name);
            }

            // If no slug is set, use the title to generate one
            if (!($this->object->slug)) {
                $this->object->slug = nggdb::get_unique_slug( sanitize_title($this->object->title), 'gallery' );
            }
        }

        // Set what will be the path to the gallery
        if (!($this->object->path))
        {
            $storage = $this->object->get_registry()->get_utility('I_Gallery_Storage');
            $this->object->path = $storage->get_upload_relpath($this->object);
            unset($storage);
        }

        $this->object->validates_presence_of('title');
		$this->object->validates_presence_of('name');
        $this->object->validates_uniqueness_of('slug');
        $this->object->validates_numericality_of('author');

		return $this->object->is_valid();
    }
}

/**
 * Creates a model representing a NextGEN Gallery object
 */
class C_Gallery extends C_DataMapper_Model
{
	var $_mapper_interface = 'I_Gallery_Mapper';

    /**
     * Defines the interfaces and methods (through extensions and hooks)
     * that this class provides
     */
    function define($properties, $mapper=FALSE, $context=FALSE)
    {
        parent::define($mapper, $properties, $context);
		$this->add_mixin('Mixin_NextGen_Gallery_Validation');
        $this->implement('I_Gallery');
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

	function get_images()
	{
		$mapper = C_Image_Mapper::get_instance();
		return $mapper->select()->where(array('galleryid = %d', $this->gid))->order_by('sortorder')->run_query();
	}
}
