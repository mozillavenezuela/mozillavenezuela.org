<?php

class A_NextGen_AddGallery_Pages extends Mixin
{
    function initialize()
    {
        $this->object->add(NGG_ADD_GALLERY_SLUG, array(
			'adapter'	=>	 'A_NextGen_AddGallery_Controller',
			'parent'	=>	NGGFOLDER,
			'add_menu'	=>	TRUE,
			'before'	=>	'nggallery-manage-gallery'
		));
    }
}
