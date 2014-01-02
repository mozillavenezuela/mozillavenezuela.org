<?php

class C_Gallery_Display_Installer
{
	function get_registry()
	{
		return C_Component_Registry::get_instance();
	}

	/**
	 * Installs a display type
	 * @param string $name
	 * @param array $properties
	 */
	function install_display_type($name, $properties=array())
	{
		// Try to find the existing entity. If it doesn't exist, we'll create
		$fs					= $this->get_registry()->get_utility('I_Fs');
		$mapper				= $this->get_registry()->get_utility('I_Display_Type_Mapper');
		$display_type		= $mapper->find_by_name($name);
		if (!$display_type)	$display_type = new stdClass;

		// Update the properties of the display type
		$properties['name'] = $name;
		foreach ($properties as $key=>$val) {
			if ($key == 'preview_image_relpath') {
				$val = $fs->find_static_relpath($val);
			}
			$display_type->$key = $val;
		}

		// Save the entity
		$retval = $mapper->save($display_type);
		return $retval;
	}

	function install_displayed_gallery_source($name, $properties)
	{
		// Try to find the existing source. If not found, then we'll create
		$mapper = $this->get_registry()->get_utility('I_Displayed_Gallery_Source_Mapper');
		$source = $mapper->find_by_name($name);
		if (!$source) $source = new stdClass;

		// Update the properties
		foreach ($properties as $key=>$val) $source->$key = $val;
		$source->name = $name;

		// Save!
		$mapper->save($source);
		unset($mapper);
	}

	/**
	 * Deletes all displayed galleries
	 */
	function uninstall_displayed_galleries()
	{
		$mapper = $this->get_registry()->get_utility('I_Displayed_Gallery_Mapper');
		$mapper->delete()->run_query();
	}

	/**
	 * Uninstalls all display types
	 */
	function uninstall_display_types()
	{
		$mapper = $this->get_registry()->get_utility('I_Display_Type_Mapper');
		$mapper->delete()->run_query();
	}

	/**
	 * Installs displayed gallery sources
	 */
	function install($reset=FALSE)
	{
		$this->install_displayed_gallery_source('galleries', array(
			'title'		=>	'Galleries',
			'returns'	=>	array('image'),
			'aliases'	=>	array('gallery', 'images', 'image')
		));

		$this->install_displayed_gallery_source('albums', array(
			'title'		=>	'Albums',
			'returns'	=>	array('gallery', 'album'),
			'aliases'	=>	array('album')
		));

		$this->install_displayed_gallery_source('tags', array(
			'title'		=>	'Tags',
			'returns'	=>	array('image'),
			'aliases'	=>	array('tag', 'image_tag', 'image_tags')
		));

		$this->install_displayed_gallery_source('random_images', array(
			'title'		=>	'Random Images',
			'returns'	=>	array('image'),
			'aliases'	=>	array('random', 'random_image'),
			'has_variations'	=>	TRUE
		));

		$this->install_displayed_gallery_source('recent_images', array(
			'title'		=>	'Recent images',
			'returns'	=>	array('image'),
			'aliases'	=>	array('recent', 'recent_image')
		));
	}

	/**
	 * Deletes all displayed gallery sources
	 */
	function uninstall_displayed_gallery_sources()
	{
		$mapper = $this->get_registry()->get_utility('I_Displayed_Gallery_Source_Mapper');
		$mapper->delete()->run_query();
	}

	/**
	 * Uninstalls this module
	 */
	function uninstall($hard = FALSE)
	{
		// Flush displayed gallery cache
		C_Photocrati_Cache::flush();
		C_Photocrati_Cache::flush('displayed_galleries');
		C_Photocrati_Cache::flush('displayed_gallery_rendering');

		$this->uninstall_display_types();
		$this->uninstall_displayed_gallery_sources();
		
		// TODO temporary Don't remove galleries on uninstall
		//if ($hard) $this->uninstall_displayed_galleries();
	}


}
