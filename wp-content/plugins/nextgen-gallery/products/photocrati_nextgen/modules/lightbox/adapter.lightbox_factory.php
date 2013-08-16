<?php

class A_Lightbox_Factory extends Mixin
{
	function lightbox_library($mapper=FALSE, $properties=array(), $context=FALSE)
	{
		return new C_Lightbox_Library($mapper, $properties, $context);
	}

	function lightbox($mapper=FALSE, $properties=array(), $context=FALSE)
	{
		return $this->object->lightbox_library($mapper, $properties, $context);
	}
}