<?php

class A_NextGen_Basic_ImageBrowser_Mapper extends Mixin
{
	/**
	 * Adds a hook for setting default values
	 */
	function initialize()
	{
		$this->object->add_post_hook(
			'set_defaults',
			'NextGen Basic ImageBrowser Defaults',
			'Hook_NextGen_Basic_ImageBrowser_Defaults',
			'set_defaults'
		);
	}
}

/**
 * Adds default values for the NextGEN Basic ImageBrowser display type
 */
class Hook_NextGen_Basic_ImageBrowser_Defaults extends Hook
{
	function set_defaults($entity)
	{
		if (isset($entity->name) && $entity->name == NGG_BASIC_IMAGEBROWSER)
        {
			$this->object->_set_default_value($entity, 'settings', 'template', '');

            // Part of the pro-modules
            $this->object->_set_default_value($entity, 'settings', 'ngg_triggers_display', 'never');
		}
	}
}