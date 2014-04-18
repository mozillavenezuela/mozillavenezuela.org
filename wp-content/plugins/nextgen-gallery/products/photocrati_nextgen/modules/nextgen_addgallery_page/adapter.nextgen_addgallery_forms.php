<?php

class A_NextGen_AddGallery_Forms extends Mixin
{
    function initialize()
    {
        $settings = C_NextGen_Settings::get_instance();
        $registry = $this->object->get_registry();

        $forms = array('upload_images' => 'A_Upload_Images_Form');
        if (!is_multisite() || (is_multisite() && $settings->get('wpmuImportFolder')))
            $forms['import_folder'] = 'A_Import_Folder_Form';

        foreach ($forms as $form => $adapter) {
            $registry->add_adapter('I_Form', $adapter, $form);
            $this->object->add_form(NGG_ADD_GALLERY_SLUG, $form);
        }
    }
}