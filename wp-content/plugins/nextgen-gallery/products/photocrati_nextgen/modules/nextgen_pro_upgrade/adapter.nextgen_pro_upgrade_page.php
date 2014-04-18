<?php

class A_NextGen_Pro_Upgrade_Page extends Mixin
{
    function initialize()
    {
        // Using include() to retrieve the is_plugin_active() is apparently The WordPress Way(tm)..
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        // We shouldn't show the upgrade page if they already have the plugin and it's active
        $found = false;
        if (defined('NEXTGEN_GALLERY_PRO_PLUGIN_BASENAME'))
            $found = 'NEXTGEN_GALLERY_PRO_PLUGIN_BASENAME';
        if (defined('NGG_PRO_PLUGIN_BASENAME'))
            $found = 'NGG_PRO_PLUGIN_BASENAME';

        if ($found && is_plugin_active(constant($found)))
            return;

        $this->object->add('ngg_pro_upgrade', array(
			'adapter'	=>		'A_NextGen_Pro_Upgrade_Controller',
			'parent'	=>		NGGFOLDER
		));
    }
}