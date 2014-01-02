<?php

class A_NextGen_Basic_Gallery_Mapper extends Mixin
{
	function initialize()
	{
		$this->object->add_post_hook(
			'set_defaults',
            'NextGen Basic Gallery Defaults',
			'Hook_NextGen_Basic_Gallery_Defaults'
		);
	}
}

/**
 * Sets default values for the NextGen Basic Slideshow display type
 */
class Hook_NextGen_Basic_Gallery_Defaults extends Hook
{
	function set_defaults($entity)
	{
		if (isset($entity->name)) {
			if ($entity->name == NEXTGEN_GALLERY_BASIC_SLIDESHOW)
				$this->set_slideshow_defaults($entity);

			else if ($entity->name == NEXTGEN_GALLERY_BASIC_THUMBNAILS)
				$this->set_thumbnail_defaults($entity);
		}
	}
    
    function set_slideshow_defaults($entity)
    {
        $settings = C_NextGen_Settings::get_instance();
        $this->object->_set_default_value($entity, 'settings', 'images_per_page', 10);
        $this->object->_set_default_value($entity, 'settings', 'gallery_width', $settings->irWidth);
        $this->object->_set_default_value($entity, 'settings', 'gallery_height', $settings->irHeight);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_width', $settings->thumbwidth);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_height', $settings->thumbheight);
        $this->object->_set_default_value($entity, 'settings', 'cycle_interval', $settings->irRotatetime);
        $this->object->_set_default_value($entity, 'settings', 'cycle_effect', $settings->slideFx);
        $this->object->_set_default_value($entity, 'settings', 'flash_enabled', $settings->enableIR);
        $this->object->_set_default_value($entity, 'settings', 'flash_path', $settings->irURL);
        $this->object->_set_default_value($entity, 'settings', 'flash_shuffle', $settings->irShuffle);
        $this->object->_set_default_value($entity, 'settings', 'flash_next_on_click', $settings->irLinkfromdisplay);
        $this->object->_set_default_value($entity, 'settings', 'flash_navigation_bar', $settings->irShownavigation);
        $this->object->_set_default_value($entity, 'settings', 'flash_loading_icon', $settings->irShowicons);
        $this->object->_set_default_value($entity, 'settings', 'flash_watermark_logo', $settings->irWatermark);
        $this->object->_set_default_value($entity, 'settings', 'flash_stretch_image', $settings->irOverstretch);
        $this->object->_set_default_value($entity, 'settings', 'flash_transition_effect', $settings->irTransition);
        $this->object->_set_default_value($entity, 'settings', 'flash_slow_zoom', $settings->irKenburns);
        $this->object->_set_default_value($entity, 'settings', 'flash_background_color', $settings->irBackcolor);
        $this->object->_set_default_value($entity, 'settings', 'flash_text_color', $settings->irFrontcolor);
        $this->object->_set_default_value($entity, 'settings', 'flash_rollover_color', $settings->irLightcolor);
        $this->object->_set_default_value($entity, 'settings', 'flash_screen_color', $settings->irScreencolor);
        $this->object->_set_default_value($entity, 'settings', 'flash_background_music', $settings->irAudio);
        $this->object->_set_default_value($entity, 'settings', 'flash_xhtml_validation', $settings->irXHTMLvalid);
        $this->object->_set_default_value($entity, 'settings', 'effect_code', $settings->thumbCode);
        $this->object->_set_default_value($entity, 'settings', 'show_thumbnail_link', $settings->galShowSlide ? 1 : 0);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_link_text', $settings->galTextGallery);
        $this->object->_set_default_value($entity, 'settings', 'template', '');

        // Part of the pro-modules
        $this->object->_set_default_value($entity, 'settings', 'ngg_triggers_display', 'never');
    }
    
    
    function set_thumbnail_defaults($entity)
    {
        $settings = C_NextGen_Settings::get_instance();
        $this->object->_set_default_value($entity, 'settings', 'images_per_page', $settings->galImages);
        $this->object->_set_default_value($entity, 'settings', 'number_of_columns', $settings->galColumns);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_width', $settings->thumbwidth);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_height', $settings->thumbheight);
        $this->object->_set_default_value($entity, 'settings', 'show_all_in_lightbox', $settings->galHiddenImg);
        $this->object->_set_default_value($entity, 'settings', 'ajax_pagination', $settings->galAjaxNav);
        $this->object->_set_default_value($entity, 'settings', 'use_imagebrowser_effect', $settings->galImgBrowser);
        $this->object->_set_default_value($entity, 'settings', 'template', '');
        $this->object->_set_default_value($entity, 'settings', 'display_no_images_error', 1);

        // TODO: Should this be called enable pagination?
        $this->object->_set_default_value($entity, 'settings', 'disable_pagination', 0);

        // Alternative view support
        $this->object->_set_default_value($entity, 'settings', 'show_slideshow_link', $settings->galShowSlide ? 1 : 0);
        $this->object->_Set_default_value($entity, 'settings', 'slideshow_link_text', $settings->galTextSlide);

        // override thumbnail settings
        $this->object->_set_default_value($entity, 'settings', 'override_thumbnail_settings', 0);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_quality', '100');
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_crop', 1);
        $this->object->_set_default_value($entity, 'settings', 'thumbnail_watermark', 0);

        // Show piclens link ?
        $this->object->_set_default_value($entity, 'settings', 'piclens_link_text', _('[Show PicLens]'));
        $this->object->_set_default_value($entity, 'settings', 'show_piclens_link',
            isset($entity->settings['show_piclens_link']) &&
              preg_match("/^true|yes|y$/", $entity->settings['show_piclens_link']) ?
                1 : 0
        );

        // Part of the pro-modules
        $this->object->_set_default_value($entity, 'settings', 'ngg_triggers_display', 'never');
    }
}
