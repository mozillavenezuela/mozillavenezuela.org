<?php

class A_NextGen_AddGallery_Forms extends Mixin
{
    function initialize()
    {
        $forms = array(
          'upload_images'   =>  'A_Upload_Images_Form',
          'import_folder'   =>  'A_Import_Folder_Form'
        );

        $registry = $this->object->get_registry();
        foreach ($forms as $form => $adapter) {
            $registry->add_adapter('I_Form', $adapter, $form);
            $this->object->add_form(
                NEXTGEN_ADD_GALLERY_SLUG,
                $form
            );
        }
    }
}