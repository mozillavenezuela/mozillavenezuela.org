<?php

class A_Miscellaneous_Form extends Mixin
{
	function get_model()
	{
		return C_Settings_Model::get_instance('global');
	}

	function get_title()
	{
		return 'Miscellaneous';
	}

	function render()
	{
		return $this->object->render_partial(
            'photocrati-nextgen_other_options#misc_tab',
            array(
                'mediarss_activated'       => C_NextGen_Settings::get_instance()->useMediaRSS,
                'mediarss_activated_label' => _('Add MediaRSS link?'),
                'mediarss_activated_help'  => _('When enabled, adds a MediaRSS link to your header. Third-party web services can use this to publish your galleries'),
                'mediarss_activated_no'    => _('No'),
                'mediarss_activated_yes'   => _('Yes'),

                'cache_label'        => _('Clear image cache'),
                'cache_confirmation' => _("Completely clear the NextGEN cache of all image modifications?\n\nChoose [Cancel] to Stop, [OK] to proceed."),

                 'slug_field' => $this->_render_text_field(
                     (object)array('name' => 'misc_settings'),
                     'router_param_slug',
                     'Permalink slug',
                     $this->object->get_model()->router_param_slug
                 ),

                'maximum_entity_count_field' => $this->_render_number_field(
                    (object)array('name' => 'misc_settings'),
                    'maximum_entity_count',
                    'Maximum image count',
                    $this->object->get_model()->maximum_entity_count,
                    'This is the maximum limit of images that NextGEN will restrict itself to querying',
                    FALSE,
                    '',
                    1
                )
            ),
            TRUE
        );
	}

    function cache_action()
    {
        $cache   = $this->get_registry()->get_utility('I_Cache');
        $cache->flush_galleries();
		C_Photocrati_Cache::flush();
		C_Photocrati_Cache::flush('displayed_galleries');
		C_Photocrati_Cache::flush('displayed_gallery_rendering');
    }

	function save_action()
	{
		if (($settings = $this->object->param('misc_settings')))
        {
			// The Media RSS setting is actually a local setting, not a global one
			$local_settings = C_NextGen_Settings::get_instance();
			$local_settings->set('useMediaRSS', $settings['useMediaRSS']);
			unset($settings['useMediaRSS']);

			// If the router slug has changed, then flush the cache
			if ($settings['router_param_slug'] != $this->object->get_model()->router_param_slug) {
				C_Photocrati_Cache::flush();
			}

			// Save both setting groups
			$this->object->get_model()->set($settings)->save();
			$local_settings->save();
		}
	}
}