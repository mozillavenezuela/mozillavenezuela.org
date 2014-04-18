<?php

/**
 * Adds validation for the NextGen Basic ImageBrowser display type
 */
class A_NextGen_Basic_ImageBrowser extends Mixin
{
	function initialize()
	{
		if ($this->object->name == NGG_BASIC_IMAGEBROWSER) {
			$this->object->add_pre_hook(
				'validation',
				__CLASS__,
				'Hook_NextGen_Basic_ImageBrowser_Validation'
			);
		}
	}
}

/**
 * Provides validation for the NextGen Basic ImageBrowser display type
 */
class Hook_NextGen_Basic_ImageBrowser_Validation extends Hook
{
	function validation()
	{
	}
}
