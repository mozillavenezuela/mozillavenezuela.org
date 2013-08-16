<?php

class Mixin_Security_Actor extends Mixin
{
	function add_capability($capability_name)
	{
		return false;
	}
	
	function remove_capability($capability_name)
	{
		return false;
	}
	
	function is_allowed($capability_name, $args = null)
	{
		return false;
	}
	
	function is_user()
	{
		return false;
	}
}

class Mixin_Security_Actor_Entity extends Mixin
{
	var $entity_object = null;
	var $entity_props = null;

	// Note, an Actor with null $entity is considered a "Guest", i.e. no privileges
	function set_entity($entity, $entity_props = null)
	{
		$this->object->entity_object = $entity;
		$this->object->entity_props = $entity_props;
	}
	
	function get_entity($entity = null)
	{
		if ($entity == null)
		{
			$entity = $this->object->entity_object;
		}
		
		if ($entity != null && $entity == $this->object->entity_object)
		{
			return $entity;
		}
		
		return null;
	}
	
	function get_entity_id($entity = null)
	{
		$entity = $this->object->get_entity($entity);
		
		if ($entity != null)
		{
			$entity_props = $this->object->entity_props;
			
			if (isset($entity_props['id']))
			{
				return $entity_props['id'];
			}
		}
		
		return null;
	}
	
	function get_entity_type($entity = null)
	{
		$entity = $this->object->get_entity($entity);
		
		if ($entity != null)
		{
			$entity_props = $this->object->entity_props;
			
			if (isset($entity_props['type']))
			{
				return $entity_props['type'];
			}
		}
		
		return null;
	}
}

// XXX not used yet
class Mixin_Security_Entity_List extends Mixin
{
	var $_entity_list;
	
	function add_entity($entity, $entity_props = null)
	{
		if (!$this->object->is_entity($entity))
		{
			$entity_props = array_merge((array) $entity_props, array('object' => $entity));
			
			$this->object->_entity_list[] = $entity_props;
		}
	}
	
	function remove_entity($entity)
	{
		if ($this->object->is_entity($entity))
		{
		}
	}
	
	function is_entity($entity)
	{
		return $this->object->get_entity_set($entity);
	}
	
	function get_entity_set($entity)
	{
		foreach ($this->_entity_list as $entity_set)
		{
			
		}
	}
	
	function get_entity_id($entity)
	{
		
	}
	
	function get_entity_type($entity)
	{
		
	}
}

class C_Security_Actor extends C_Component
{
	function define($context=FALSE)
	{
		parent::define($context);

		$this->implement('I_Security_Actor');
		$this->add_mixin('Mixin_Security_Actor');
		$this->add_mixin('Mixin_Security_Actor_Entity');
	}
}
