<?php

class A_Lightbox_Factory extends Mixin
{
	function lightbox_library($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		return new C_Lightbox_Library($properties, $mapper, $context);
	}

	function lightbox($properties=array(), $mapper=FALSE, $context=FALSE)
	{
		return $this->object->lightbox_library($properties, $mapper, $context);
	}
}