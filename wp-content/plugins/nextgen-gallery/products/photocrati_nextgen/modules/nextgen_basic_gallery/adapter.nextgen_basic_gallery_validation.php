<?php

class A_NextGen_Basic_Gallery_Validation extends Mixin
{
    function initialize()
    {
        if ($this->object->name == NGG_BASIC_THUMBNAILS) {
            $this->object->add_pre_hook(
                'validation',
                get_class(),
                'Hook_NextGen_Basic_Thumbnail_Validation'
            );
        }
        else if ($this->object->name == NGG_BASIC_SLIDESHOW) {
            $this->object->add_pre_hook(
                'validation',
                get_class(),
                'Hook_NextGen_Basic_Slideshow_Validation'
            );
        }
        
    }
}

class Hook_NextGen_Basic_Slideshow_Validation extends Hook
{
    function validation()
    {
        $this->object->validates_presence_of('gallery_width');
		$this->object->validates_presence_of('gallery_height');
		$this->object->validates_numericality_of('gallery_width');
		$this->object->validates_numericality_of('gallery_height');
    }
}

class Hook_NextGen_Basic_Thumbnail_Validation extends Hook
{
    function validation()
    {
        $this->object->validates_presence_of('thumbnail_width');
		$this->object->validates_presence_of('thumbnail_height');
		$this->object->validates_numericality_of('thumbnail_width');
		$this->object->validates_numericality_of('thumbnail_height');
		$this->object->validates_numericality_of('images_per_page');   
    }
}