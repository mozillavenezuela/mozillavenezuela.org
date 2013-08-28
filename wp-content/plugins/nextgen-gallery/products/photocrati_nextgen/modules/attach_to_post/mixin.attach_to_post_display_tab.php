<?php

/**
 * Provides the "Display Tab" for the Attach To Post interface/controller
 */
class Mixin_Attach_To_Post_Display_Tab extends Mixin
{
	/**
	 * Renders the JS required for the Backbone-based Display Tab
	 */
	function display_tab_js_action($return=FALSE)
	{
        // Cache appropriately
        $this->object->do_not_cache();

        // Ensure that JS is returned
        $this->object->set_content_type('javascript');
				    
				while (ob_get_level() > 0) {
					ob_end_clean();
				}

        // Get all entities used by the display tab
        $context = 'attach_to_post';
        $gallery_mapper		= $this->get_registry()->get_utility('I_Gallery_Mapper',		$context);
        $album_mapper		= $this->get_registry()->get_utility('I_Album_Mapper',			$context);
        $display_type_mapper= $this->get_registry()->get_utility('I_Display_Type_Mapper',	$context);
        $source_mapper		= $this->get_registry()->get_utility('I_Displayed_Gallery_Source_Mapper', $context);
        $security			= $this->get_registry()->get_utility('I_Security_Manager');

        // Get the nextgen tags
        global $wpdb;
        $tags = $wpdb->get_results(
                        "SELECT DISTINCT name AS 'id', name FROM {$wpdb->terms}
                        WHERE term_id IN (
                                SELECT term_id FROM {$wpdb->term_taxonomy}
                                WHERE taxonomy = 'ngg_tag'
                        )");
        $all_tags = new stdClass;
        $all_tags->name = "All";
        $all_tags->id   = "All";
        array_unshift($tags, $all_tags);
        
        $display_types = $display_type_mapper->find_all();
        
        usort($display_types, array($this->object, '_display_type_list_sort'));

        $output = $this->object->render_view('photocrati-attach_to_post#display_tab_js', array(
                'displayed_gallery'		=>	json_encode($this->object->_displayed_gallery->get_entity()),
                'sources'				=>	json_encode($source_mapper->select()->order_by('title')->run_query()),
                'gallery_primary_key'	=>	$gallery_mapper->get_primary_key_column(),
                'galleries'				=>	json_encode($gallery_mapper->find_all()),
                'albums'				=>	json_encode($album_mapper->find_all()),
                'tags'					=>	json_encode($tags),
                'display_types'			=>	json_encode($display_types),
                'sec_token'				=>	$security->get_request_token('nextgen_edit_displayed_gallery')->get_json()
        ), $return);
        
        return $output;
	}
	
	function _display_type_list_sort($type_1, $type_2)
	{
		$order_1 = $type_1->view_order;
		$order_2 = $type_2->view_order;
		
		if ($order_1 == null) {
			$order_1 = NEXTGEN_DISPLAY_PRIORITY_BASE;
		}
		
		if ($order_2 == null) {
			$order_2 = NEXTGEN_DISPLAY_PRIORITY_BASE;
		}
		
		if ($order_1 > $order_2) {
			return 1;
		}
		
		if ($order_1 < $order_2) {
			return -1;
		}
		
		return 0;
	}


	/**
	 * Gets a list of tabs to render for the "Display" tab
	 */
	function _get_display_tabs()
	{
		// The ATP requires more memmory than some applications, somewhere around 60MB.
		// Because it's such an important feature of NextGEN Gallery, we temporarily disable
		// any memory limits
		@ini_set('memory_limit', -1);

		return array(
			$this->object->_render_display_types_tab(),
			$this->object->_render_display_source_tab(),
			$this->object->_render_display_settings_tab(),
			$this->object->_render_preview_tab()
		);
	}


	/**
	 * Renders the accordion tab, "What would you like to display?"
	 */
	function _render_display_source_tab()
	{
		return $this->object->render_partial('photocrati-attach_to_post#accordion_tab', array(
			'id'			=> 'source_tab',
			'title'		=>	_('What would you like to display?'),
			'content'	=>	$this->object->_render_display_source_tab_contents()
		), TRUE);
	}


	/**
	 * Renders the contents of the source tab
	 * @return string
	 */
	function _render_display_source_tab_contents()
	{
		return $this->object->render_partial('photocrati-attach_to_post#display_tab_source', array(),TRUE);
	}


	/**
	 * Renders the accordion tab for selecting a display type
	 * @return string
	 */
	function _render_display_types_tab()
	{
		return $this->object->render_partial('photocrati-attach_to_post#accordion_tab', array(
			'id'			=> 'display_type_tab',
			'title'		=>	_('Select a display type'),
			'content'	=>	$this->object->_render_display_type_tab_contents()
		), TRUE);
	}


	/**
	 * Renders the contents of the display type tab
	 */
	function _render_display_type_tab_contents()
	{
		return $this->object->render_partial('photocrati-attach_to_post#display_tab_type', array(), TRUE);
	}


	/**
	 * Renders the display settings tab for the Attach to Post interface
	 * @return type
	 */
	function _render_display_settings_tab()
	{
		return $this->object->render_partial('photocrati-attach_to_post#accordion_tab', array(
			'id'			=> 'display_settings_tab',
			'title'		=>	_('Customize the display settings'),
			'content'	=>	$this->object->_render_display_settings_contents()
		), TRUE);
	}

	/**
	 * If editing an existing displayed gallery, retrieves the name
	 * of the display type
	 * @return string
	 */
	function _get_selected_display_type_name()
	{
		$retval = '';

		if ($this->object->_displayed_gallery)
			$retval = $this->object->_displayed_gallery->display_type;

		return $retval;
	}


	/**
	 * Is the displayed gallery that's being edited using the specified display
	 * type?
	 * @param string $name	name of the display type
	 * @return bool
	 */
	function is_displayed_gallery_using_display_type($name)
	{
		$retval = FALSE;

		if ($this->object->_displayed_gallery) {
			$retval = $this->object->_displayed_gallery->display_type == $name;
		}

		return $retval;
	}


	/**
	 * Renders the contents of the display settings tab
	 * @return string
	 */
	function _render_display_settings_contents()
	{
		$retval = array();

		// Get all display setting forms
        $form_manager = C_Form_Manager::get_instance();
		$forms		  = $form_manager->get_forms(
			NEXTGEN_DISPLAY_SETTINGS_SLUG, TRUE
		);

		// Display each form
		foreach ($forms as $form) {

			// Enqueue the form's static resources
			$form->enqueue_static_resources();

			// Determine which classes to use for the form's "class" attribute
			$model = $form->get_model();
			$current = $this->object->is_displayed_gallery_using_display_type($model->name);
			$css_class =  $current ? 'display_settings_form' : 'display_settings_form hidden';

			// If this form is used to provide the display settings for the current
			// displayed gallery, then we need to override the forms settings
			// with the displayed gallery settings
			if ($current) {
				$settings = $this->array_merge_assoc(
					$model->settings,
					$this->object->_displayed_gallery->display_settings,
					TRUE
				);
				
				$model->settings = $settings;
			}
			
			// Output the display settings form
			$retval[] = $this->object->render_partial('photocrati-attach_to_post#display_settings_form', array(
				'settings'				=>	$form->render(),
				'display_type_name'		=>	$model->name,
				'css_class'				=>	$css_class
			), TRUE);
		}

		// In addition, we'll render a form that will be displayed when no
		// display type has been selected in the Attach to Post interface
		// Render the default "no display type selected" view
		$css_class = $this->object->_get_selected_display_type_name() ?
			'display_settings_form hidden' : 'display_settings_form';
		$retval[] = $this->object->render_partial('photocrati-attach_to_post#no_display_type_selected', array(
			'no_display_type_selected'	=>	_('No display type selected'),
			'css_class'					=>	$css_class

		), TRUE);

		// Return all display setting forms
		return implode("\n", $retval);
	}


	/**
	 * Renders the tab used to preview included images
	 * @return string
	 */
	function _render_preview_tab()
	{
		return $this->object->render_partial('photocrati-attach_to_post#accordion_tab', array(
			'id'			=> 'preview_tab',
			'title'		=>	_('Sort or Exclude Images'),
			'content'	=>	$this->object->_render_preview_tab_contents()
		), TRUE);
	}


	/**
	 * Renders the contents of the "Preview" tab.
	 * @return string
	 */
	function _render_preview_tab_contents()
	{
		return $this->object->render_partial('photocrati-attach_to_post#preview_tab', array(), TRUE);
	}
}
