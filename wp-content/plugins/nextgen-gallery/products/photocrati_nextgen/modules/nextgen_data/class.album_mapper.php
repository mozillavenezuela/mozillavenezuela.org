<?php

class C_Album_Mapper extends C_CustomTable_DataMapper_Driver
{
    static $_instances = array();

	function initialize($object_name=FALSE)
	{
		parent::initialize('ngg_album');
	}

	function define($context=FALSE, $not_used=FALSE)
	{
		// Define the context
		if (!is_array($context)) $context = array($context);
		array_push($context, 'album');
		$this->_primary_key_column = 'id';

		// Define the mapper
		parent::define('ngg_album', $context);
		$this->add_mixin('Mixin_NextGen_Table_Extras');
		$this->add_mixin('Mixin_Album_Mapper');
		$this->implement('I_Album_Mapper');
		$this->set_model_factory_method('album');

		// Define the columns
		$this->define_column('id', 'BIGINT', 0);
		$this->define_column('name', 'VARCHAR(255)');
		$this->define_column('slug', 'VARCHAR(255');
		$this->define_column('previewpic', 'BIGINT', 0);
		$this->define_column('albumdesc', 'TEXT');
		$this->define_column('sortorder', 'TEXT');
		$this->define_column('pageid', 'BIGINT', 0);

		// Mark the columns which should be unserialized
		$this->add_serialized_column('sortorder');
	}

    /**
     * Returns an instance of the album datamapper
     * @param bool|mixed $context
     * @return C_Album_Mapper
     */
    static function get_instance($context=FALSE)
    {
        if (!isset(self::$_instances[$context])) {
            $klass = get_class();
            self::$_instances[$context] = new $klass($context);
        }
        return self::$_instances[$context];
    }
}


/**
 * Provides album-specific methods for the datamapper
 */
class Mixin_Album_Mapper extends Mixin
{
    /**
     * Gets the post title when the Custom Post driver is used
     * @param C_DataMapper_Model|C_Album|stdClass $entity
     * @return string
     */
    function get_post_title($entity)
	{
		return $entity->name;
	}

	function _save_entity($entity)
	{
		$retval = $this->call_parent('_save_entity', $entity);
		if ($retval) {
			C_Photocrati_Cache::flush('displayed_gallery_rendering');
		}
		return $retval;
	}

    /**
     * Sets the defaults for an album
     * @param C_DataMapper_Model|C_Album|stdClass $entity
     */
    function set_defaults($entity)
    {
        $this->object->_set_default_value($entity, 'name', '');
        $this->object->_set_default_value($entity, 'albumdesc', '');
        $this->object->_set_default_value($entity, 'sortorder', array());
        $this->object->_set_default_value($entity, 'previewpic', 0);
		$this->object->_set_default_value($entity, 'exclude', 0);
        $this->object->_set_default_value(
            $entity,
            'slug',
            nggdb::get_unique_slug( sanitize_title( $entity->name ), 'album' )
        );
    }
}