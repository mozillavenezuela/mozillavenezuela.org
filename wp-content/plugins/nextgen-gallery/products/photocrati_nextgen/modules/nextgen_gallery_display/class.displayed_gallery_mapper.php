<?php

class C_Displayed_Gallery_Mapper extends C_CustomPost_DataMapper_Driver
{
	static $_instances = array();

	function define($context=FALSE, $not_used=FALSE)
	{
		parent::define('displayed_gallery', array($context, 'displayed_gallery', 'display_gallery'));
		$this->add_mixin('Mixin_Displayed_Gallery_Defaults');
		$this->implement('I_Displayed_Gallery_Mapper');
		$this->set_model_factory_method('displayed_gallery');
//		$this->add_post_hook(
//			'save',
//			'Propagate thumbnail dimensions',
//			'Hook_Propagate_Thumbnail_Dimensions_To_Settings'
//		);
	}


	/**
	 * Initializes the mapper
	 * @param string|array|FALSE $context
	 */
	function initialize()
	{
		parent::initialize('displayed_gallery');
	}


	/**
	 * Gets a singleton of the mapper
	 * @param string|array $context
	 * @return C_Displayed_Gallery_Mapper
	 */
    public static function get_instance($context = False)
    {
        if (!isset(self::$_instances[$context]))
        {
            self::$_instances[$context] = new C_Displayed_Gallery_Mapper($context);
        }
        return self::$_instances[$context];
    }
}

/**
 * Adds default values for the displayed gallery
 */
class Mixin_Displayed_Gallery_Defaults extends Mixin
{
	/**
	 * Gets a display type object for a particular entity
	 * @param stdClass|C_DataMapper_Model $entity
	 * @return null|stdClass
	 */
	function get_display_type($entity)
	{
		$mapper = $this->object->get_registry()->get_utility('I_Display_Type_Mapper');
		return $mapper->find_by_name($entity->display_type);
	}

	/**
	 * Sets defaults needed for the entity
	 * @param type $entity
	 */
	function set_defaults($entity)
	{
		// Ensure that we have a settings array
		if (!isset($entity->display_settings)) $entity->display_settings = array();

		// If the display type is set, then get it's settings and apply them as
		// defaults to the "display_settings" of the displayed gallery
		if (isset($entity->display_type)) {

			// Get display type mapper
			if (($display_type = $this->object->get_display_type($entity))) {
				$entity->display_settings = $this->array_merge_assoc(
					$display_type->settings, $entity->display_settings, TRUE
				);
			}
		}

		// Default ordering
		$settings = C_NextGen_Settings::get_instance();
		$this->object->_set_default_value($entity, 'order_by', $settings->galSort);
		$this->object->_set_default_value($entity, 'order_direction', $settings->galSortDir);

        // Ensure we have an exclusions array
        $this->object->_set_default_value($entity, 'exclusions', array());

		// Ensure other properties exist
		$this->object->_set_default_value($entity, 'container_ids', array());
		$this->object->_set_default_value($entity, 'excluded_container_ids', array());
        $this->object->_seT_default_value($entity, 'sortorder',     array());
		$this->object->_set_default_value($entity, 'entity_ids', array());
		$this->object->_set_default_value($entity, 'returns', 'included');

		// Set maximum_entity_count
		$this->object->_set_default_value($entity, 'maximum_entity_count', $settings->maximum_entity_count);
	}
}
