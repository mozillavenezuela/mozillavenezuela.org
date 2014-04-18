<?php

/***
{
		Module: photocrati-nextgen-data,
		Depends: { photocrati-datamapper }
}
***/

class M_NextGen_Data extends C_Base_Module
{
    function define()
    {
        parent::define(
            'photocrati-nextgen-data',
            'NextGEN Data Tier',
            "Provides a data tier for NextGEN gallery based on the DataMapper module",
            '0.8',
            'http://www.photocrati.com',
            'Photocrati Media',
            'http://www.photocrati.com'
        );

		C_Photocrati_Installer::add_handler($this->module_id, 'C_NextGen_Data_Installer');
    }

    function _register_adapters()
    {
        $this->get_registry()->add_adapter('I_Component_Factory', 'A_NextGen_Data_Factory');
		#$this->get_registry()->add_adapter('I_CustomPost_DataMapper', 'A_Attachment_DataMapper', 'attachment');
		$this->get_registry()->add_adapter('I_CustomTable_DataMapper', 'A_CustomTable_Sorting_DataMapper');
        $this->get_registry()->add_adapter('I_Installer', 'A_NextGen_Data_Installer');
    }


    function _register_utilities()
    {
		$this->get_registry()->add_utility('I_Gallery_Mapper', 'C_Gallery_Mapper');
		$this->get_registry()->add_utility('I_Image_Mapper', 'C_Image_Mapper');
        $this->get_registry()->add_utility('I_Album_Mapper', 'C_Album_Mapper');
        $this->get_registry()->add_utility('I_Gallery_Storage', 'C_Gallery_Storage');
    }

    function get_type_list()
    {
        return array(
            'A_Attachment_Datamapper' => 'adapter.attachment_datamapper.php',
            'A_Customtable_Sorting_Datamapper' => 'adapter.customtable_sorting_datamapper.php',
            'A_Nextgen_Data_Factory' => 'adapter.nextgen_data_factory.php',
            'C_NextGen_Data_Installer' => 'class.nextgen_data_installer.php',
            'A_Parse_Image_Metadata' => 'adapter.parse_image_metadata.php',
            'C_Album' => 'class.album.php',
            'C_Gallery' => 'class.gallery.php',
            'C_Image' => 'class.image.php',
            'C_Album_Mapper' => 'class.album_mapper.php',
            'C_Gallerystorage_Base' => 'class.gallerystorage_base.php',
            'C_Gallerystorage_Driver_Base' => 'class.gallerystorage_driver_base.php',
            'C_Gallery_Mapper' => 'class.gallery_mapper.php',
            'C_Gallery_Storage' => 'class.gallery_storage.php',
            'C_Image_Mapper' => 'class.image_mapper.php',
            'C_Image_Wrapper' => 'class.image_wrapper.php',
            'C_Image_Wrapper_Collection' => 'class.image_wrapper_collection.php',
            'C_Nextgen_Metadata' => 'class.nextgen_metadata.php',
			'Mixin_NextGen_Table_Extras'	=>	'mixin.nextgen_table_extras.php',
            'C_Ngglegacy_Gallerystorage_Driver' => 'class.ngglegacy_gallerystorage_driver.php',
            'C_Ngglegacy_Thumbnail' => 'class.ngglegacy_thumbnail.php',
            'C_Wordpress_Gallerystorage_Driver' => 'class.wordpress_gallerystorage_driver.php',
            'I_Album' => 'interface.album.php',
            'I_Gallery' => 'interface.gallery.php',
            'I_Image' => 'interface.image.php',
            'I_Album_Mapper' => 'interface.album_mapper.php',
            'I_Component_Config' => 'interface.component_config.php',
            'I_Gallerystorage_Driver' => 'interface.gallerystorage_driver.php',
            'I_Gallery_Mapper' => 'interface.gallery_mapper.php',
            'I_Gallery_Storage' => 'interface.gallery_storage.php',
            'I_Gallery_Type' => 'interface.gallery_type.php',
            'I_Image_Mapper' => 'interface.image_mapper.php'
        );
    }
    
    
    function _register_hooks()
    {
		add_action('init', array(&$this, 'register_custom_post_types'));
    	add_filter('posts_orderby', array($this, 'wp_query_order_by'), 10, 2);
    }

	function register_custom_post_types()
	{
		$types = array(
			'ngg_album'		=>	'NextGEN Gallery - Album',
			'ngg_gallery'	=>	'NextGEN Gallery - Gallery',
			'ngg_pictures'	=>	'NextGEN Gallery - Image',
		);

		foreach ($types as $type => $label) {
			register_post_type($type, array(
				'label'					=>	$label,
				'publicly_queryable'	=>	FALSE,
				'exclude_from_search'	=>	TRUE,
			));
		}
	}
    
    function wp_query_order_by($order_by, $wp_query)
    {
    	if ($wp_query->get('datamapper_attachment'))
    	{
    		$order_parts = explode(' ', $order_by);
    		$order_name = array_shift($order_parts);
    		
    		$order_by = 'ABS(' . $order_name . ') ' . implode(' ', $order_parts) . ', ' . $order_by;
    	}
    	
    	return $order_by;
    }
}
new M_NextGen_Data();
