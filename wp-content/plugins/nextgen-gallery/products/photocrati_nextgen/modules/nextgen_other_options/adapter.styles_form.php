<?php

class A_Styles_Form extends Mixin
{
	function get_model()
	{
		return C_Settings_Model::get_instance();
	}

	function get_title()
	{
		return 'Styles';
	}

	function render()
	{
		return $this->object->render_partial('photocrati-nextgen_other_options#styling_tab', array(
			'select_stylesheet_label'	=>	'What stylesheet would you like to use?',
			'stylesheets'				=>	C_NextGen_Style_Manager::get_instance()->find_all_stylesheets(),
			'activated_stylesheet'		=>	$this->object->get_model()->CSSfile,
			'hidden_label'				=>	_('(Show Customization Options)'),
			'active_label'				=>	_('(Hide Customization Options)'),
			'cssfile_contents_label'	=>	_('File Content:'),
			'writable_label'			=>	_('Changes you make to the contents will be saved to'),
			'readonly_label'			=>	_('You could edit this file if it were writable')
		), TRUE);
	}

	function save_action()
	{
		// Ensure that we have
		if (($settings = $this->object->param('style_settings'))) {
			$this->object->get_model()->set($settings)->save();

			// Are we to modify the CSS file?
			if (($contents = $this->object->param('cssfile_contents'))) {

				// Find filename
				$css_file		= $settings['CSSfile'];
				$styles = C_NextGen_Style_Manager::get_instance();
				$styles->save($contents, $css_file);
			}
		}
	}
}
