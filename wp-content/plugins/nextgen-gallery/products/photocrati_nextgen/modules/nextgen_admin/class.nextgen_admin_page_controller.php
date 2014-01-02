<?php

class C_NextGen_Admin_Page_Controller extends C_MVC_Controller
{
	static $_instances = array();

	static function &get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}

	function define($context=FALSE)
	{
		if (is_array($context)) $this->name = $context[0];
		else $this->name = $context;

		parent::define($context);
		$this->add_mixin('Mixin_NextGen_Admin_Page_Instance_Methods');
		$this->implement('I_NextGen_Admin_Page');
	}

	function initialize()
	{
		parent::initialize();
		$this->add_pre_hook(
			'index_action',
			'Enqueue Backend Resources',
			'Hook_NextGen_Admin_Page_Resources',
			'enqueue_backend_resources'
		);
	}
}

class Hook_NextGen_Admin_Page_Resources extends Hook
{
	function enqueue_backend_resources()
	{
		$this->object->enqueue_backend_resources();
	}
}


class Mixin_NextGen_Admin_Page_Instance_Methods extends Mixin
{
	/**
	 * Authorizes the request
	 */
	function is_authorized_request($privilege=NULL)
	{
		if (!$privilege) $privilege = $this->object->get_required_permission();
		$security = $this->get_registry()->get_utility('I_Security_Manager');
		$retval = $sec_token = $security->get_request_token(str_replace(array(' ', "\n", "\t"), '_', $privilege));
		$sec_actor = $security->get_current_actor();

		// Ensure that the user has permission to access this page
		if (!$sec_actor->is_allowed($privilege))
			$retval = FALSE;

		// Ensure that nonce is valid
		if ($this->object->is_post_request() && !$sec_token->check_current_request()) {
			$retval = FALSE;
		}

		return $retval;
	}

	/**
	 * Returns the permission required to access this page
	 * @return string
	 */
	function get_required_permission()
	{
		return $this->object->name;
	}

	/**
	 * Enqueues resources required by a NextGEN Admin page
	 */
	function enqueue_backend_resources()
	{
		$atp = $this->object->get_registry()->get_utility('I_Attach_To_Post_Controller');
		
		wp_enqueue_script('jquery');
		$this->object->enqueue_jquery_ui_theme();
		wp_enqueue_script('jquery-ui-accordion');
		wp_enqueue_script(
            'nextgen_display_settings_page_placeholder_stub',
            $this->get_static_url('photocrati-nextgen_admin#jquery.placeholder.min.js'),
            array('jquery'),
            '2.0.7',
            TRUE
        );
		wp_register_script('iris', $this->get_router()->get_url('/wp-admin/js/iris.min.js', FALSE, TRUE), array('jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch'));
		wp_register_script('wp-color-picker', $this->get_router()->get_url('/wp-admin/js/color-picker.js', FALSE, TRUE), array('iris'));
		wp_localize_script('wp-color-picker', 'wpColorPickerL10n', array(
			'clear' => __( 'Clear' ),
			'defaultString' => __( 'Default' ),
			'pick' => __( 'Select Color' ),
			'current' => __( 'Current Color' ),
		));
		wp_enqueue_script(
			'nextgen_admin_page',
			$this->get_static_url('photocrati-nextgen_admin#nextgen_admin_page.js'),
            array('wp-color-picker')
		);
		wp_enqueue_style(
			'nextgen_admin_page',
			$this->get_static_url('photocrati-nextgen_admin#nextgen_admin_page.css'),
            array('wp-color-picker')
		);

		// Ensure select2
		wp_enqueue_style('select2');
		wp_enqueue_script('select2');
		
		if ($atp != null) {
			$atp->mark_script('jquery-ui-accordion');
			$atp->mark_script('nextgen_display_settings_page_placeholder_stub');
			$atp->mark_script('iris');
			$atp->mark_script('wp-color-picker');
			$atp->mark_script('nextgen_admin_page');
			$atp->mark_script('select2');
		}
	}

	function enqueue_jquery_ui_theme()
	{
		$settings = C_NextGen_Settings::get_instance();
		wp_enqueue_style(
			$settings->jquery_ui_theme,
			is_ssl() ?
				 str_replace('http:', 'https:', $settings->jquery_ui_theme_url) :
				 $settings->jquery_ui_theme_url,
			NULL,
			$settings->jquery_ui_theme_version
		);
	}

	/**
	 * Returns the page title
	 * @return string
	 */
	function get_page_title()
	{
		return $this->object->name;
	}

	/**
	 * Returns the page heading
	 * @return string
	 */
	function get_page_heading()
	{
		return $this->object->get_page_title();
	}

	/**
	 * Returns the type of forms to render on this page
	 * @return string
	 */
	function get_form_type()
	{

		return is_array($this->object->context) ?
			$this->object->context[0] : $this->object->context;
	}

	function get_success_message()
	{
		return "Saved successfully";
	}


	/**
	 * Returns an accordion tab, encapsulating the form
	 * @param I_Form $form
	 */
	function to_accordion_tab($form)
	{
		return $this->object->render_partial('photocrati-nextgen_admin#accordion_tab', array(
			'id'		=>	$form->get_id(),
			'title'		=>	$form->get_title(),
			'content'	=>	$form->render(TRUE)
		), TRUE);
	}

	/**
	 * Returns the
	 * @return type
	 */
	function get_forms()
	{
		$forms = array();
        $form_manager = C_Form_Manager::get_instance();
		foreach ($form_manager->get_forms($this->object->get_form_type()) as $form) {
			$forms[] = $this->get_registry()->get_utility('I_Form', $form);
		}
		return $forms;
	}

	/**
	 * Gets the action to be executed
	 * @return string
	 */
	function _get_action()
	{
		$retval = preg_quote($this->object->param('action'), '/');
		$retval = strtolower(preg_replace(
			"/[^\w]/",
			'_',
			$retval
		));
		return preg_replace("/_{2,}/", "_", $retval).'_action';
	}

	/**
	 * Returns the template to be rendered for the index action
	 * @return string
	 */
	function index_template()
	{
		return 'photocrati-nextgen_admin#nextgen_admin_page';
	}

    function show_save_button()
    {
        return TRUE;
    }

	/**
	 * Renders a NextGEN Admin Page using jQuery Accordions
	 */
	function index_action()
	{
		if (($token = $this->object->is_authorized_request())) {
			// Get each form. Validate it and save any changes if this is a post
			// request
			$tabs			= array();
			$errors			= array();
			$success		= $this->object->is_post_request() ?
									$this->object->get_success_message() : '';

			foreach ($this->object->get_forms() as $form) {
				$form->enqueue_static_resources();
				if ($this->object->is_post_request()) {
					$action = $this->object->_get_action();
					if ($form->has_method($action)) {
                        $form->$action($this->object->param($form->context));
					}
				}

                $tabs[] = $this->object->to_accordion_tab($form);

                if ($form->has_method('get_model') && $form->get_model()) {
                    if ($form->get_model()->is_invalid()) {
                        if (($form_errors = $this->object->show_errors_for($form->get_model(), TRUE))) {
                            $errors[] = $form_errors;
                        }
                        $form->get_model()->clear_errors();
                    }
                }
			}

			// Render the view
			$this->render_partial($this->object->index_template(), array(
				'page_heading'		=>	$this->object->get_page_heading(),
				'tabs'				=>	$tabs,
				'errors'			=>	$errors,
				'success'			=>	$success,
				'form_header'		=>  $token->get_form_html(),
                'show_save_button'  =>  $this->object->show_save_button()
			));
		}

		// The user is not authorized to view this page
		else {
			$this->render_view('photocrati-nextgen_admin#not_authorized', array(
				'name'	=>	$this->object->name,
				'title'	=>	$this->object->get_page_title()
			));
		}
	}
}
