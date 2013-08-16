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
	function define($mapper, $properties, $context=FALSE)
	{
		parent::define($mapper, $properties, $context);
		$this->add_mixin('Mixin_Lightbox_Library_Validation');
		$this->implement('I_Lightbox_Library');
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