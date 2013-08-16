<?php

class A_Import_Folder_Form extends Mixin
{
    function get_title()
    {
        return "Import Folder";
    }

    function enqueue_static_resources()
    {
        wp_enqueue_style('jquery.filetree');
        wp_enqueue_style('ngg_progressbar');
        wp_enqueue_script('jquery.filetree');
        wp_enqueue_script('ngg_progressbar');
    }

    function render()
    {
        return $this->object->render_partial('photocrati-nextgen_addgallery_page#import_folder', array(
        ), TRUE);
    }
}