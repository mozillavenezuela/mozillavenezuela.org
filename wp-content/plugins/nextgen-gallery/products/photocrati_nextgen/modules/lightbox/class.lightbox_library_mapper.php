<?php

/**
 * Provides CRUD operations for lightbox libraries
 */
class C_Lightbox_Library_Mapper extends C_CustomPost_DataMapper_Driver
{
	static $_instances = array();

	/**
	 * Defines the lightbox library datamapper
	 * @param type $context
	 */
	function define($context = FALSE)
	{
		if (!is_array($context)) $context = array($context);
		array_push($context, 'lightbox_library');
		parent::define('lightbox_library', $context);
		$this->add_mixin('Mixin_Lightbox_Library_Mapper');
		$this->implement('I_Lightbox_Library_Mapper');
		$this->set_model_factory_method('lightbox_library');
	}

	/**
	 * Initializes the datamapper
	 */
	function initialize()
	{
		parent::initialize('lightbox_library');
	}

	/**
	 * Returns an instance of the mapper
	 * @param string|FALSE $context
	 * @return C_Lightbox_Library_Mapper
	 */
	static function get_instance($context=FALSE)
	{
		$klass = get_class();
		if (!isset(self::$_instances[$context])) {
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}
}

class Mixin_Lightbox_Library_Mapper
{
	/**
	 * Uses the name property as the post title when the Custom Post driver
	 * is used
	 * @param stdClass $entity
	 * @return string
	 */
	function get_post_title($entity)
	{
		return $entity->name;
	}


	/**
	 * Selects a lightbox library by name
	 * @param string $name
	 * @param type $model
	 */
	function find_by_name($name, $model=FALSE)
	{
		$results = $this->object->select()->where(array('name = %s', $name))->run_query(FALSE, $model);
		if ($results) $results = $results[0];
		return $results;
	}

	/**
	 * Sets default values for the lightbox library
	 * @param stdClass|C_DataMapper_Model $entity
	 */
	function set_defaults($entity)
	{
		$this->object->_set_default_value($entity, 'styles', '');
		$this->object->_set_default_value($entity, 'scripts', '');
        $this->object->_set_default_value($entity, 'display_settings', array());
	}
}