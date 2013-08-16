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
		if ($entity->name == NEXTGEN_BASIC_TAG_CLOUD_MODULE_NAME) {
			$this->object->_set_default_value(
				$entity,
				'settings',
				'display_type',
				'photocrati-nextgen_basic_thumbnails'
			);
		}
	}
}