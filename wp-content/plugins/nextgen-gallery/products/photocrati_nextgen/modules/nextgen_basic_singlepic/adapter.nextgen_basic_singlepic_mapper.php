<?php

class A_NextGen_Basic_SinglePic_Mapper extends Mixin
{
	function initialize()
	{
		$this->object->add_post_hook(
			'set_defaults',
			get_class(),
			get_class(),
			'_set_singlepic_defaults'
		);

		if ($this->object->has_context('attach_to_post')) {
			$this->object->add_post_hook(
				'run_query',
				get_class(),
				get_class(),
				'_remove_singlepic_from_results'
			);
		}
	}

	/**
	 * Removes the singlepic display type from a resultset to hide it from
	 * the Attach to Post interface
	 */
	function _remove_singlepic_from_results()
	{
		$retval = array();

		// Get all of the returned display types
		$results = $this->object->get_method_property(
			$this->method_called, ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE
		);

		// Iterate through each display type to be returned, and remove the
		// SinglePic display type
		foreach ($results as &$display_type) {
			if (!((isset($display_type->name) && $display_type->name == NEXTGEN_BASIC_SINGLEPIC_MODULE_NAME))) {
				$retval[] = $display_type;
			}
		}

		// Set the new return value
		$this->object->set_method_property(
			$this->method_called,
			ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE,
			$retval
		);

		return $retval;
	}

	/**
	 * Sets default values for SinglePic settings
	 * @param stdClass|C_DataMapper_Model $entity
	 */
	function _set_singlepic_defaults($entity)
	{
		if ($entity->name == NEXTGEN_BASIC_SINGLEPIC_MODULE_NAME) {
			$this->object->_set_default_value($entity, 'settings', 'width', '');
			$this->object->_set_default_value($entity, 'settings', 'height', '');
			$this->object->_set_default_value($entity, 'settings', 'mode', '');
			$this->object->_set_default_value($entity, 'settings', 'display_watermark', 0);
			$this->object->_set_default_value($entity, 'settings', 'display_reflection', 0);
			$this->object->_set_default_value($entity, 'settings', 'float', '');
			$this->object->_set_default_value($entity, 'settings', 'link', '');
			$this->object->_set_default_value($entity, 'settings', 'quality', 100);
			$this->object->_set_default_value($entity, 'settings', 'crop', 0);
            $this->object->_set_default_value($entity, 'settings', 'template', '');

            // Part of the pro-modules
            $this->object->_set_default_value($entity, 'settings', 'ngg_triggers_display', 'never');
		}
	}
}
