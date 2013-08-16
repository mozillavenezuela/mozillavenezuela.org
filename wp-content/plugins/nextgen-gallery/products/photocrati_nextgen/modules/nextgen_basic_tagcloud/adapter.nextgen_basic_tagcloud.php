<?php

class A_NextGen_Basic_Tagcloud extends Mixin
{
    function initialize()
    {
        if ($this->object->name == NEXTGEN_BASIC_TAG_CLOUD_MODULE_NAME) {
            $this->object->add_pre_hook(
				'validation',
				get_class(),
				'Hook_NextGen_Basic_Tagcloud_Validation'
			);
        }
    }
}

class Hook_NextGen_Basic_Tagcloud_Validation extends Hook
{
    function validation()
    {
        $this->object->validates_presence_of('display_type');
    }
}
