<?php

class A_NextGen_AddGallery_Pages extends Mixin
{
    function initialize()
    {
        $this->object->add(
            NEXTGEN_ADD_GALLERY_SLUG,
            'A_NextGen_AddGallery_Controller',
            NGGFOLDER,
            true,
            'nggallery-manage-gallery'
        );
    }
}
