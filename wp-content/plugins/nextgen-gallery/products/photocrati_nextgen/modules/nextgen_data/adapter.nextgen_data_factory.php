<?php

class A_NextGen_Data_Factory extends Mixin
{
	function gallery($mapper=FALSE, $properties=array(), $context=FALSE)
    {
        return new C_Gallery($properties, $mapper, $context);
    }


    function gallery_image($mapper=FALSE, $properties=array(), $context=FALSE)
    {
        return new C_Image($properties, $mapper, $context);
    }


    function image($mapper=FALSE, $properties=array(), $context=FALSE)
    {
        return new C_Image($properties, $mapper, $context);
    }


    function album($mapper=FALSE, $properties=array(), $context=FALSE)
    {
        return new C_Album($mapper, $properties, $context);
    }


	function ngglegacy_gallery_storage($context=FALSE)
	{
		return new C_NggLegacy_GalleryStorage_Driver($context);
	}


	function wordpress_gallery_storage($context=FALSE)
	{
		return new C_WordPress_GalleryStorage_Driver($context);
	}


	function gallery_storage($context=FALSE)
	{
		return new C_Gallery_Storage($context);
	}


	function gallerystorage($context=FALSE)
	{
		return $this->object->gallery_storage($context);
	}
}