<?php

class A_NextGen_Settings_Routes extends Mixin
{
    function initialize()
    {
        $this->object->add_pre_hook(
            'serve_request',
            'Adds NextGen Admin-Settings routes',
            get_class(),
            'add_nextgen_settings_routes'
        );
    }

    function add_nextgen_settings_routes()
    {
        $this->create_app('/nextgen-settings')
             ->route('/update_watermark_preview', 'I_Settings_Manager_Controller#watermark_update');
    }
}