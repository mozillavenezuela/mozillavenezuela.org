<?php
/*
{
	Module: photocrati-nextgen_other_options,
	Depends: { photocrati-nextgen_admin }
}
 */

define('NGG_OTHER_OPTIONS_SLUG', 'ngg_other_options');

class M_NextGen_Other_Options extends C_Base_Module
{
	function define()
	{
		parent::define(
			'photocrati-nextgen_other_options',
			'Other Options',
			'NextGEN Gallery Others Options Page',
			'0.7',
			'http://www.nextgen-gallery.com',
			'Photocrati Media',
			'http://www.photocrati.com'
		);
	}

    function _register_hooks()
    {
        add_action('admin_bar_menu', array(&$this, 'add_admin_bar_menu'), 101);
    }

    function add_admin_bar_menu()
    {
        global $wp_admin_bar;

        if ( current_user_can('NextGEN Change options') ) {
            $wp_admin_bar->add_menu(array(
                'parent' => 'ngg-menu',
                'id' => 'ngg-menu-other_options',
                'title' => __('Other Options', 'nggallery'),
                'href' => admin_url('admin.php?page=ngg_other_options')
            ));
        }
    }

	function _register_adapters()
	{
		$this->get_registry()->add_adapter(
			'I_Page_Manager',
			'A_Other_Options_Page'
		);

		$this->get_registry()->add_adapter(
			'I_Form_Manager',
			'A_Other_Options_Forms'
		);

		$this->get_registry()->add_adapter(
			'I_Ajax_Controller',
			'A_Watermarking_Ajax_Actions'
		);

        $this->get_registry()->add_adapter(
            'I_Ajax_Controller',
            'A_Stylesheet_Ajax_Actions'
        );
	}

    function get_type_list()
    {
        return array(
            'A_Image_Options_Form' => 'adapter.image_options_form.php',
            'A_Lightbox_Manager_Form' => 'adapter.lightbox_manager_form.php',
            'A_Miscellaneous_Form' => 'adapter.miscellaneous_form.php',
            'A_Other_Options_Controller' => 'adapter.other_options_controller.php',
            'A_Other_Options_Forms' => 'adapter.other_options_forms.php',
            'A_Other_Options_Page' => 'adapter.other_options_page.php',
            'A_Reset_Form' => 'adapter.reset_form.php',
            'A_Roles_Form' => 'adapter.roles_form.php',
            'A_Styles_Form' => 'adapter.styles_form.php',
            'A_Thumbnail_Options_Form' => 'adapter.thumbnail_options_form.php',
            'A_Watermarking_Ajax_Actions' => 'adapter.watermarking_ajax_actions.php',
            'A_Watermarks_Form' => 'adapter.watermarks_form.php',
            'A_Stylesheet_Ajax_Actions' => 'adapter.stylesheet_ajax_actions.php',
			'C_Settings_Model'	=>	'class.settings_model.php'
        );
    }
}

new M_NextGen_Other_Options;
