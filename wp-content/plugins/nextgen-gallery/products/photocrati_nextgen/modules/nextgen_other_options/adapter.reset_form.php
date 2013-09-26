<?php

class A_Reset_Form extends Mixin
{
	function get_title()
	{
		return 'Reset Options';
	}

	function render()
	{
		return $this->object->render_partial(
            'photocrati-nextgen_other_options#reset_tab',
            array(
                'reset_value'			=> _('Reset all options to default settings'),
                'reset_warning'			=> _('Replace all existing options and gallery options with their default settings'),
                'reset_label'			=> _('Reset settings'),
                'reset_confirmation'	=> _("Reset all options to default settings?\n\nChoose [Cancel] to Stop, [OK] to proceed.")
                // 'uninstall_label'		=> _('Deactivate & Uninstall'),
				// 'uninstall_confirmation'=>_("Completely uninstall NextGEN Gallery (will reset settings and de-activate)?\n\nChoose [Cancel] to Stop, [OK] to proceed."),
            ),
            TRUE
        );
	}

	function reset_action()
	{
        global $wpdb;

		$installer = C_Photocrati_Installer::get_instance();
        $settings  = C_NextGen_Settings::get_instance();

        // removes lightbox, display type, and source settings
		$installer->uninstall(NEXTGEN_GALLERY_PLUGIN_BASENAME);

        // removes ngg_options entry in wp_options
        $settings->destroy();
        $settings->save();

        // TODO: remove this sometime after 2.0.21
        //
        // Some installations of NextGen that upgraded from 1.9x to 2.0x have duplicate display types installed,
        // so for now (as of 2.0.21) we explicitly remove all display types from the db as a way of fixing this
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_type = %s", 'display_type'));

        // trigger the install routine
		$installer->update(TRUE);
	}

    /*
	function uninstall_action()
	{
		$installer = C_Photocrati_Installer::get_instance();
		$installer->uninstall(NEXTGEN_GALLERY_PLUGIN_BASENAME, TRUE);
		deactivate_plugins(NEXTGEN_GALLERY_PLUGIN_BASENAME);
		wp_redirect(admin_url('/plugins.php'));
	}
    */
}
