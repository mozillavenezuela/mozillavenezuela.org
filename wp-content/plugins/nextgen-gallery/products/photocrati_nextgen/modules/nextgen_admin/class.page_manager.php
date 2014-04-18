<?php

class C_Page_Manager extends C_Component
{
	static $_instances = array();
	var $_pages = array();

	/**
	 * Gets an instance of the Page Manager
	 * @param string $context
	 * @return C_Page_Manager
	 */
	static function &get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}

	/**
	 * Defines the instance of the Page Manager
	 * @param type $context
	 */
	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Page_Manager');
		$this->implement('I_Page_Manager');
	}
}

class Mixin_Page_Manager extends Mixin
{
	function add($slug, $properties=array())
	{
		if (!isset($properties['adapter'])) $properties['adapter']	= NULL;
		if (!isset($properties['parent']))	$properties['parent']	= NULL;
		if (!isset($properties['add_menu']))$properties['add_menu']	= TRUE;
		if (!isset($properties['before']))	$properties['before']	= NULL;
		if (!isset($properties['url']))		$properties['url']		= NULL;

		$this->object->_pages[$slug] = $properties;
	}
	
	function move_page($slug, $other_slug, $after = false)
	{
		$page_list = $this->object->_pages;
		
		if (isset($page_list[$slug]) && isset($page_list[$other_slug]))
		{
			$slug_list = array_keys($page_list);
			$item_list = array_values($page_list);
			
			$slug_idx = array_search($slug, $slug_list);
			$item = $page_list[$slug];
			
			unset($slug_list[$slug_idx]);
			unset($item_list[$slug_idx]);
			
			$slug_list = array_values($slug_list);
			$item_list = array_values($item_list);
			
			$other_idx = array_search($other_slug, $slug_list);
			
			array_splice($slug_list, $other_idx, 0, array($slug));
			array_splice($item_list, $other_idx, 0, array($item));
			
			$this->object->_pages = array_combine($slug_list, $item_list);
		}
	}

	function remove($slug)
	{
		unset($this->object->_pages[$slug]);
	}

	function get_all()
	{
		return $this->object->_pages;
	}

	function setup()
	{
		$registry		= $this->get_registry();
		$controllers	= array();
		foreach ($this->object->_pages as $slug => $properties) {

			$page_title 	= "Unnamed Page";
			$menu_title		= "Unnamed Page";
			$permission		= NULL;
			$callback 		= NULL;

			// There's two type of pages we can have. Some are powered by our controllers, and others
			// are powered by WordPress, such as a custom post type page.

			// Is this powered by a controller? If so, we expect an adapter
			if ($properties['adapter']) {

				// Register the adapter and instantiate the controller
				$registry->add_adapter(
					'I_NextGen_Admin_Page',
					$properties['adapter'],
					$slug
				);
				$controllers[$slug] = $registry->get_utility(
					'I_NextGen_Admin_Page',
					$slug
				);

				$menu_title = $controllers[$slug]->get_page_heading();
				$page_title = $controllers[$slug]->get_page_title();
				$permission = $controllers[$slug]->get_required_permission();
				$callback 	= array(&$controllers[$slug], 'index_action');
			}

			// Is this page powered by another url, such as one that WordPres provides?
			elseif ($properties['url']) {
				$slug = $properties['url'];
				if (isset($properties['menu_title'])) {
					$menu_title = $properties['menu_title'];
				}
				if (isset($properties['permission'])) {
					$permission = $properties['permission'];
				}
			}

			// Are we to add a menu?
			if ($properties['add_menu']) {

				add_submenu_page(
					$properties['parent'],
					$page_title,
					$menu_title,
					$permission,
					$slug,
					$callback
				);
				
				if ($properties['before']) {
					global $submenu;
					
					$parent = $submenu[$properties['parent']];
					$item_index = -1;
					$before_index = -1;
					
					if ($parent != null) {
						foreach ($parent as $index => $menu) {
						
							// under add_submenu_page, $menu_slug is index 2
							// $submenu[$parent_slug][] = array ( $menu_title, $capability, $menu_slug, $page_title );
							if ($menu[2] == $slug) {
								$item_index = $index;
							}
							else if ($menu[2] == $properties['before']) {
								$before_index = $index;
							}
						}
					}
				
					if ($item_index > -1 && $before_index > -1) {
				
						$item = $parent[$item_index];
					
						unset($parent[$item_index]);
						$parent = array_values($parent);
					
						if ($item_index < $before_index) 
							$before_index--;
						
						array_splice($parent, $before_index, 0, array($item));
					
						$submenu[$properties['parent']] = $parent;
					}
				}
			}
		}
	}
}
