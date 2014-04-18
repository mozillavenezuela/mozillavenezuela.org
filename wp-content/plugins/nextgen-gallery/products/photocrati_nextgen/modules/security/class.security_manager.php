<?php

class Mixin_Security_Manager extends Mixin
{
	function is_allowed($capability_name, $args = null)
	{
		$actor = $this->object->get_current_actor();
		
		if ($actor != null)
		{
			return $actor->is_allowed($capability_name, $args);
		}
		
		return false;
	}
	
	function get_actor($actor_id, $actor_type = null, $args = null)
	{
		return null;
	}
	
	function get_current_actor()
	{
		return null;
	}
}

class Mixin_Security_Manager_Request extends Mixin
{
	function get_request_token($action_name, $args = null)
	{
		return null;
	}
}

class C_Security_Manager extends C_Component
{
    static $_instances = array();

    function define($context=FALSE)
    {
			parent::define($context);

			$this->implement('I_Security_Manager');
			$this->add_mixin('Mixin_Security_Manager');
			$this->add_mixin('Mixin_Security_Manager_Request');
    }

    static function get_instance($context = False)
    {
			if (!isset(self::$_instances[$context]))
			{
					self::$_instances[$context] = new C_Security_Manager($context);
			}

			return self::$_instances[$context];
    }
}
