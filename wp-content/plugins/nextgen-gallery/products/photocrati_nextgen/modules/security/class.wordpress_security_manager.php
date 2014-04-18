<?php

class Mixin_WordPress_Security_Manager extends Mixin
{
	function get_actor($actor_id, $actor_type = null, $args = null)
	{
		if ($actor_type == null)
		{
			$actor_type = 'user';
		}

		$object = null;

		if ($actor_id != null)
		{
			switch ($actor_type)
			{
				case 'user':
				{
					$object = get_userdata($actor_id);

					if ($object == false)
					{
						$object = null;
					}

					break;
				}
				case 'role':
				{
					$object = get_role($actor_id);

					if ($object == false)
					{
						$object = null;
					}

					break;
				}
			}
		}

		if ($object != null)
		{
			$factory = $this->get_registry()->get_utility('I_Component_Factory');
			$actor	 = $factory->create('wordpress_security_actor', $actor_type);
			$entity_props = array(
				'type' => $actor_type,
				'id' => $actor_id,
			);

			$actor->set_entity($object, $entity_props);

			return $actor;
		}

		return $this->object->get_guest_actor();
	}

	function get_current_actor()
	{
		// If the current_user has an id of 0, then perhaps something went wrong
		// with trying to parse the cookie. In that case, we'll force WordPress to try
		// again
		global $current_user;
		if ($current_user->ID == 0) {
			if (isset($GLOBALS['HTTP_COOKIE_VARS']) && isset($GLOBALS['_COOKIE'])) {
                $current_user = NULL;
                foreach ($GLOBALS['HTTP_COOKIE_VARS'] as $key => $value) {
                    if (!isset($_COOKIE[$key])) {
                        $_COOKIE[$key] = $value;
                    }
                }
            }
		}

		return $this->object->get_actor(get_current_user_id(), 'user');
	}

	function get_guest_actor()
	{
		$factory = $this->get_registry()->get_utility('I_Component_Factory');
		$actor   = $factory->create('wordpress_security_actor', 'user');
		$entity_props = array(
			'type' => 'user'
		);

		$actor->set_entity(null, $entity_props);

		return $actor;
	}
}

class Mixin_WordPress_Security_Manager_Request extends Mixin
{
	function get_request_token($action_name, $args = null)
	{
		$factory = $this->get_registry()->get_utility('I_Component_Factory');
		$token	 = $factory->create('wordpress_security_token');
		$token->init_token($action_name, $args);

		return $token;
	}
}

class C_WordPress_Security_Manager extends C_Security_Manager
{
    static $_instances = array();

    function define($context=FALSE)
    {
		parent::define($context);

		$this->add_mixin('Mixin_WordPress_Security_Manager');
		$this->add_mixin('Mixin_WordPress_Security_Manager_Request');
    }

    static function get_instance($context = False)
    {
		if (!isset(self::$_instances[$context]))
		{
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}

		return self::$_instances[$context];
    }
}
