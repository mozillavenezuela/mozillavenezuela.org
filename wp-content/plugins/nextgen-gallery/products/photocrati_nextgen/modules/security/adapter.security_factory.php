<?php

class A_Security_Factory extends Mixin
{
	function wordpress_security_manager($context=FALSE)
	{
		return new C_WordPress_Security_Manager($context);
	}

	function security_manager($context=FALSE)
	{
		return $this->object->wordpress_security_manager($context);
	}

	function wordpress_security_actor($context=FALSE)
	{
		return new C_WordPress_Security_Actor($context);
	}

	function wordpress_security_token($context=FALSE)
	{
		return new C_Wordpress_Security_Token($context);
	}

	function security_token($context)
	{
		return $this->object->wordpress_security_token($context);
	}
}
