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
	function add($slug, $adapter, $parent=NULL, $add_menu=TRUE, $before = NULL)
	{
		$this->object->_pages[$slug] = array(
			'adapter'	=>	$adapter,
			'parent'	=>	$parent,
			'add_menu'	=>	$add_menu,
			'before' => $before
		);
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

	function remove_page($slug)
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
			$registry->add_adapter(
				'I_NextGen_Admin_Page',
				$properties['adapter'],
				$slug
			);
			$controllers[$slug] = $registry->get_utility(
				'I_NextGen_Admin_Page',
				$slug
			);
			if ($properties['add_menu']) {
				add_submenu_page(
					$properties['parent'],
					$controllers[$slug]->get_page_title(),
					$controllers[$slug]->get_page_heading(),
					$controllers[$slug]->get_required_permission(),
					$slug,
					array(&$controllers[$slug], 'index_action')
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
