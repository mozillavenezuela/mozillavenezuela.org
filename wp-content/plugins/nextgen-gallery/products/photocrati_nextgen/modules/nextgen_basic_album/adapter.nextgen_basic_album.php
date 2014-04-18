<?php

class A_NextGen_Basic_Album extends Mixin
{
    /**
     * Adds a hook to perform validation for albums
     */
    function initialize()
    {
		$ngglegacy_albums = array(
			NGG_BASIC_COMPACT_ALBUM,
			NGG_BASIC_EXTENDED_ALBUM
		);
		if (in_array($this->object->name, $ngglegacy_albums)) {
            $this->object->add_pre_hook(
              'validation',
              'NextGEN Basic Album Validation',
              'Hook_NextGen_Basic_Album_Validation'
            );
        }
    }
    
		function get_order()
		{
			return NGG_DISPLAY_PRIORITY_BASE + NGG_DISPLAY_PRIORITY_STEP;
		}
}

/**
 * Provides validation for NextGen Basic Albums
 */
class Hook_NextGen_Basic_Album_Validation extends Hook
{
    function validation()
    {
      $this->object->validates_presence_of('gallery_display_type');
      $this->object->validates_numericality_of('galleries_per_page');
    }
}
