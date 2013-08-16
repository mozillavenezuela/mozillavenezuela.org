<?php
/*
{
	Module: photocrati-nextgen_pro_upgrade,
	Depends: { photocrati-nextgen_admin }
}
*/

class M_NextGen_Pro_Upgrade extends C_Base_Module
{
    function define()
    {
        parent::define(
            'photocrati-nextgen_pro_upgrade',
            'NextGEN Pro Page',
            'NextGEN Gallery Pro Upgrade Page',
            '0.1',
            'http://www.nextgen-gallery.com',
            'Photocrati Media',
            'http://www.photocrati.com'
        );
    }

    function _register_adapters()
    {
        $this->get_registry()->add_adapter('I_Page_Manager', 'A_NextGen_Pro_Upgrade_Page');
    }

    function get_type_list()
    {
        return array(
            'A_NextGen_Pro_Upgrade_Controller' => 'adapter.nextgen_pro_upgrade_controller.php',
            'A_NextGen_Pro_Upgrade_Page' => 'adapter.nextgen_pro_upgrade_page.php'
        );
    }
}

new M_NextGen_Pro_Upgrade;
