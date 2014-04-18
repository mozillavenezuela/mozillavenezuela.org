<?php

class Mixin_WordPress_Security_Actor extends Mixin
{
	function add_capability($capability_name)
	{
		$entity = $this->object->get_entity();
		
		if ($entity != null)
		{
			$capability_name = $this->object->get_native_action($capability_name);
			
			$entity->add_cap($capability_name);
			
			return true;
		}
		
		return false;
	}
	
	function remove_capability($capability_name)
	{
		$entity = $this->object->get_entity();
		
		if ($entity != null && $this->object->is_allowed($capability_name))
		{
			$capability_name = $this->object->get_native_action($capability_name);
			
			$entity->remove_cap($capability_name);
			
			return true;
		}
		
		return false;
	}
	
	function is_allowed($capability_name, $args = null)
	{
		$entity = $this->object->get_entity();
		
		if ($entity != null)
		{
			$capability_name = $this->object->get_native_action($capability_name, $args);
			
			return $entity->has_cap($capability_name);
		}
		
		return false;
	}
	
	function is_user()
	{
		return $this->object->get_entity_type() == 'user';
	}
	
	function get_native_action($capability_name, $args = null)
	{
		return $capability_name;
	}
}

class Mixin_WordPress_Security_Action_Converter extends Mixin
{
	function get_native_action($capability_name, $args = null)
	{
		switch ($capability_name)
		{
			case 'nextgen_edit_settings':
			{
				$capability_name = 'NextGEN Change options';
				
				break;
			}
			case 'nextgen_edit_style':
			{
				$capability_name = 'NextGEN Change style';
				
				break;
			}
			case 'nextgen_edit_display_settings':
			{
				$capability_name = 'NextGEN Change options';
				
				break;
			}
			case 'nextgen_edit_displayed_gallery':
			{
				$capability_name = 'NextGEN Attach Interface';
				
				break;
			}
			case 'nextgen_edit_gallery':
			{
				$capability_name = 'NextGEN Manage gallery';
				
				break;
			}
			case 'nextgen_edit_gallery_unowned':
			{
				$capability_name = 'NextGEN Manage others gallery';
				
				break;
			}
			case 'nextgen_upload_image':
			{
				$capability_name = 'NextGEN Upload images';
				
				break;
			}
			case 'nextgen_edit_album_settings':
			{
				$capability_name = 'NextGEN Edit album settings';

				break;
			}

			case 'nextgen_edit_album':
			{
				$capability_name = 'NextGEN Edit album';

				break;
			}
		}
		
		return $capability_name;
	}
}

class C_WordPress_Security_Actor extends C_Security_Actor
{
	function define($context=FALSE)
	{
		parent::define($context);

		$this->add_mixin('Mixin_WordPress_Security_Actor');
		$this->add_mixin('Mixin_WordPress_Security_Action_Converter');
	}
}
