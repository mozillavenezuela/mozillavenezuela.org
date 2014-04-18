<?php

class A_NextGen_Basic_Album_Mapper extends Mixin
{
    /**
     * Adds a hook for setting default values
     */
    function initialize()
    {
        $this->object->add_post_hook(
            'set_defaults',
            'NextGen Basic Album Defaults',
            'Hook_NextGen_Basic_Album_Defaults',
            'set_defaults'
        );
    }
}


class Hook_NextGen_Basic_Album_Defaults extends Hook
{
    function set_defaults($entity)
    {
		if (isset($entity->name) && in_array($entity->name, array(
		  NGG_BASIC_COMPACT_ALBUM,
		  NGG_BASIC_EXTENDED_ALBUM))) {

			// Set defaults for both display (album) types
            $settings = C_NextGen_Settings::get_instance();
            $this->object->_set_default_value($entity, 'settings', 'galleries_per_page', $settings->galPagedGalleries);
            $this->object->_set_default_value($entity, 'settings', 'disable_pagination',  0);
            $this->object->_set_default_value($entity, 'settings', 'template', '');

            // Thumbnail dimensions -- only used by extended albums
            if ($entity->name == NGG_BASIC_EXTENDED_ALBUM)
            {
                $this->_set_default_value($entity, 'settings', 'override_thumbnail_settings', 0);
                $this->_set_default_value($entity, 'settings', 'thumbnail_width',   $settings->thumbwidth);
                $this->_set_default_value($entity, 'settings', 'thumbnail_height',  $settings->thumbheight);
                $this->_set_default_value($entity, 'settings', 'thumbnail_quality', $settings->thumbquality);
                $this->_set_default_value($entity, 'settings', 'thumbnail_crop',    $settings->thumbfix);
                $this->_set_default_value($entity, 'settings', 'thumbnail_watermark', 0);
            }

            if (defined('NGG_BASIC_THUMBNAILS'))
                $this->object->_set_default_value($entity, 'settings', 'gallery_display_type', NGG_BASIC_THUMBNAILS);
        }
    }
}