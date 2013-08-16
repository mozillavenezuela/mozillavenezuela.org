<?php

class A_NextGen_Pro_Upgrade_Page extends Mixin
{
    function initialize()
    {
        // Using include() to retrieve the is_plugin_active() is apparently The WordPress Way(tm)..
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        // We shouldn't show the upgrade page if they already have the plugin and it's active
        if (defined('NEXTGEN_GALLERY_PRO_PLUGIN_BASENAME') && is_plugin_active(NEXTGEN_GALLERY_PRO_PLUGIN_BASENAME))
            return;

        $this->object->add(
            'ngg_pro_upgrade',
            'A_NextGen_Pro_Upgrade_Controller',
            NGGFOLDER
        );
    }
}