<?php

/***
{
Product: photocrati-nextgen
}
 ***/

define('NEXTGEN_GALLERY_CHANGE_OPTIONS_CAP', 'NextGEN Manage gallery');

class P_Photocrati_NextGen extends C_Base_Product
{
	static $modules = array(
		'photocrati-fs',
		'photocrati-validation',
		'photocrati-router',
		'photocrati-wordpress_routing',
		'photocrati-security',
		'photocrati-lzw',
		'photocrati-nextgen_settings',
		'photocrati-mvc',
		'photocrati-ajax',
		'photocrati-dynamic_stylesheet',
		'photocrati-frame_communication',
		'photocrati-datamapper',
		'photocrati-nextgen-legacy',
		'photocrati-nextgen-data',
		'photocrati-dynamic_thumbnails',
		'photocrati-nextgen_admin',
		'photocrati-nextgen_addgallery_page',
		'photocrati-nextgen_pagination',
		'photocrati-nextgen_gallery_display',
		'photocrati-attach_to_post',
		'photocrati-nextgen_other_options',
		'photocrati-nextgen_pro_upgrade',
		'photocrati-jsconsole',
		'photocrati-mediarss',
		'photocrati-cache',
		'photocrati-lightbox',
		'photocrati-nextgen_basic_templates',
		'photocrati-nextgen_basic_gallery',
		'photocrati-nextgen_basic_imagebrowser',
		'photocrati-nextgen_basic_singlepic',
		'photocrati-nextgen_basic_tagcloud',
		'photocrati-nextgen_basic_album',
		'photocrati-widget',
		'photocrati-third_party_compat',
		'photocrati-nextgen_xmlrpc'
	);

	function define()
	{
		parent::define(
			'photocrati-nextgen',
			'Photocrati NextGEN',
			'Photocrati NextGEN',
			'2.0.40',
			'http://www.nextgen-gallery.com',
			'Photocrati Media',
			'http://www.photocrati.com'
		);

		$module_path = path_join(dirname(__FILE__), 'modules');
		$this->get_registry()->set_product_module_path($this->module_id, $module_path);
		$this->get_registry()->add_module_path($module_path, TRUE, FALSE);

		foreach (self::$modules as $module_name) $this->_get_registry()->load_module($module_name);

		include_once('class.nextgen_product_installer.php');
		C_Photocrati_Installer::add_handler($this->module_id, 'C_NextGen_Product_Installer');
	}
}

new P_Photocrati_NextGen();