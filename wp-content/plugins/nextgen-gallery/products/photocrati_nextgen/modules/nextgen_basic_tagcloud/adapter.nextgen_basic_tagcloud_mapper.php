<?php

class A_NextGen_Basic_TagCloud_Mapper extends Mixin
{
	function initialize()
	{
		$this->object->add_post_hook(
			'set_defaults',
			'NextGen Basic TagCloud Default Values',
			'Hook_NextGen_Basic_TagCloud_Defaults'
		);
	}
}

class Hook_NextGen_Basic_TagCloud_Defaults extends Hook
{
	function set_defaults($entity)
	{
		if (isset($entity->name) && $entity->name == NGG_BASIC_TAGCLOUD)
        {
			$this->object->_set_default_value($entity, 'settings', 'display_type', NGG_BASIC_THUMBNAILS);
            $this->object->_set_default_value($entity, 'settings', 'number', 45);
		}
	}
}