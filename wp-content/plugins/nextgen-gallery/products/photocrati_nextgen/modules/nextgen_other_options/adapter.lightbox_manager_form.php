<?php

class A_Lightbox_Manager_Form extends Mixin
{
	function get_model()
	{
		return C_Settings_Model::get_instance();
	}

	function get_title()
	{
		return 'Lightbox Effects';
	}

	function render()
	{
        $form_manager = C_Form_Manager::get_instance();
		$mapper       = $this->object->get_registry()->get_utility('I_Lightbox_Library_Mapper');

        // retrieve and render the settings forms for each library
        $sub_fields = array();
        $advanced_fields = array();

        foreach ($form_manager->get_forms(NEXTGEN_LIGHTBOX_OPTIONS_SLUG, TRUE) as $form) {
            $form->enqueue_static_resources();
            $sub_fields[$form->context] = $form->render(FALSE);
        }

        foreach ($form_manager->get_forms(NEXTGEN_LIGHTBOX_ADVANCED_OPTIONS_SLUG, TRUE) as $form) {
            $form->enqueue_static_resources();
            $advanced_fields[$form->context] = $form->render(FALSE);
        }

		// Render container tab
		return $this->render_partial(
            'photocrati-nextgen_other_options#lightbox_library_tab',
            array(
                'lightbox_library_label' => _('What effect would you like to use?'),
                'libs'       => $mapper->find_all(),
                'id_field'   => $mapper->get_primary_key_column(),
                'selected'   => $this->object->get_model()->thumbEffect,
                'sub_fields' => $sub_fields,
                'adv_fields' => $advanced_fields
            ),
            TRUE
        );
	}

    function save_action()
	{
		// Ensure that a lightbox library was selected
		if (($id = $this->object->param('lightbox_library_id')))
        {
			$settings = $this->object->get_model();

			// Get the lightbox library mapper and find the library selected
			$mapper = $this->object->get_registry()->get_utility('I_Lightbox_Library_Mapper');
			$library = $mapper->find($id, TRUE);

			// If a valid library, we have updated settings from the user, then
			// try saving the changes
			if ($library)
            {
				if (($params = $this->object->param($library->name))) {
            		// bind our new values, use display_settings if it isn't a part of the core library
					foreach ($params as $k => $v) {
		                if (isset($library->$k)) {
		                    $library->$k = $v;
		                }
		                else {
		                    $library->display_settings[$k] = $v;
		                }

		            }
		            
					$mapper->save($library);
				}
		
				// If the requested changes weren't valid, add the validation
				// errors to the C_NextGen_Settings object
				if ($settings->is_invalid())
                {
					foreach ($library->get_errors() as $property => $errs) {
						foreach ($errs as $error) {
                            $settings->add_error($error, $property);
                        }
					}
				}
				// The lightbox library update was successful. Update C_NextGen_Settings
				else {
					$settings->thumbEffect = $library->name;
					$settings->thumbCode   = $library->code;
					$settings->save();
				}
			}
		}
	}
}
