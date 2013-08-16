<?php

class C_MVC_View_Element
{
	var $_id;
	var $_type;
	var $_list;
	var $_context;
	
	function __construct($id, $type = null)
	{
		$this->_id = $id;
		$this->_type = $type;
		$this->_list = array();
		$this->_context = array();
	}
	
	function get_id()
	{
		return $this->_id;
	}
	
	function append($child)
	{
		$this->_list[] = $child;
	}
	
	function insert($child, $position = 0)
	{
		array_splice($this->_list, $position, 0, $child);
	}
	
	function delete($child)
	{
		$index = array_search($child, $this->_list);
		
		if ($index !== false)
		{
			array_splice($this->_list, $index, 1);
		}
	}
	
	function find($id, $recurse = false)
	{
		$list = array();
		
		$this->_find($list, $id, $recurse);
		
		return $list;
	}
	
	function _find(array &$list, $id, $recurse = false)
	{
		foreach ($this->_list as $index => $element)
		{
			if ($element instanceof C_MVC_View_Element)
			{
				if ($element->get_id() == $id)
				{
					$list[] = $element;
				}
				
				if ($recurse)
				{
					$element->_find($list, $id, $recurse);
				}
			}
		}
	}
	
	function get_context($name)
	{
		if (isset($this->_context[$name]))
		{
			return $this->_context[$name];
		}
		
		return null;
	}
	
	function set_context($name, $value)
	{
		$this->_context[$name] = $value;
	}
	
	function get_object()
	{
		return $this->get_context('object');
	}
	
	// XXX not implemented
	function parse()
	{
		
	}
	
	function rasterize()
	{
		$ret = null;
		
		foreach ($this->_list as $index => $element)
		{
			if ($element instanceof C_MVC_View_Element)
			{
				$ret .= $element->rasterize();
			}
			else
			{
				$ret .= (string) $element;
			}
		}
		
		return $ret;
	}
}

