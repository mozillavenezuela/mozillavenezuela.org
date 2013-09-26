<?php

/**
 * A Controller which displays the settings form for the display type, as
 * well as the front-end display
 */
class C_Display_Type_Controller extends C_MVC_Controller
{
	static $_instances = array();

	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Display_Type_Controller');
		$this->implement('I_Display_Type_Controller');
	}


	/**
	 * Provides default behavior for rendering fields
	 * @param string $method
	 * @param array $args
	 */
	function __call($method, $args)
	{
		if (preg_match("/render_([\w_]+)/", $method, $matches) && !$this->has_method($method)) {
			$field_name = $matches[1];
			$value = isset($this->_display_type->$field_name) ?
				$this->_display_type->$field_name : '';
			return $this->render_partial($field_name, array(
				'value' => $value, 'context' => $this->_display_type->context), TRUE
			);
		}
		else {
			return parent::__call($method, $args);
		}
	}


	/**
	 * Gets a singleton of the mapper
	 * @param string|array $context
	 * @return C_Display_Type_Controller
	 */
    public static function get_instance($context = FALSE)
    {
        if (!isset(self::$_instances[$context]))
        {
            self::$_instances[$context] = new C_Display_Type_Controller($context);
        }
        return self::$_instances[$context];
    }
}

/**
 * Provides instance methods for the C_Display_Type_Controller class
 */
class Mixin_Display_Type_Controller extends Mixin
{
	var $_render_mode;
	
	/**
	 * Enqueues static resources required for lightbox effects
	 * @param type $displayed_gallery
	 */
	function enqueue_lightbox_resources($displayed_gallery)
	{
		// Enqueue the lightbox effect library
		$settings	= C_NextGen_Settings::get_instance();
		$mapper		= $this->object->get_registry()->get_utility('I_Lightbox_Library_Mapper');
		$library	= $mapper->find_by_name($settings->thumbEffect);

        // Make the path to the static resources available for libraries
        // Shutter-Reloaded in particular depends on this
        $this->object->_add_script_data(
            'ngg_common',
            'nextgen_lightbox_settings',
            array('static_path' => $this->object->get_static_relpath('', 'photocrati-lightbox')),
            TRUE,
            FALSE
        );

        {
			$i=0;
			foreach (explode("\n", $library->scripts) as $script) {
				wp_enqueue_script(
					$library->name.'-'.$i,
					$script
				);
				if ($i == 0 AND isset($library->values)) {
					foreach ($library->values as $name => $value) {
						$this->object->_add_script_data(
							$library->name . '-0',
							$name,
							$value,
							FALSE
						);
					}
				}
				$i+=1;
			}
			$i=0;
			foreach (explode("\n", $library->css_stylesheets) as $style) {
				wp_enqueue_style(
					$library->name.'-'.$i,
					$style
				);
				$i+=1;
			}
		}
	}


	/**
	 * This method should be overwritten by other adapters/mixins, and call
	 * wp_enqueue_script() / wp_enqueue_style()
	 */
	function enqueue_frontend_resources($displayed_gallery)
	{
        // This script provides common JavaScript among all display types
        wp_enqueue_script('ngg_common');

		// Enqueue the display type library
		wp_enqueue_script($displayed_gallery->display_type, $this->object->_get_js_lib_url($displayed_gallery));

        // Add "galleries = {};"
        $this->object->_add_script_data(
            'ngg_common',
            'galleries',
            new stdClass,
            TRUE,
            FALSE
        );

        // Add "galleries.gallery_1 = {};"
        $this->object->_add_script_data(
            'ngg_common',
            'galleries.gallery_' . $displayed_gallery->id(),
            (array)$displayed_gallery->get_entity(),
            FALSE
        );

        $this->object->enqueue_lightbox_resources($displayed_gallery);
	}

	function enqueue_ngg_styles()
	{
		wp_enqueue_style(
			'nggallery',
			C_NextGen_Style_Manager::get_instance()->get_selected_stylesheet_url()
		);
	}
	
	function get_render_mode()
	{
		return $this->object->_render_mode;
	}
	
	function set_render_mode($mode)
	{
		$this->object->_render_mode = $mode;
	}

	/**
	* Ensures that the minimum configuration of parameters are sent to a view
	* @param $displayed_gallery
	* @param null $params
	* @return array|null
	*/
	function prepare_display_parameters($displayed_gallery, $params = null)
	{
		if ($params == null)
		{
			$params = array();
		}
		
		$params['display_type_rendering'] = true;
		$params['displayed_gallery'] = $displayed_gallery;
		$params['render_mode'] = $this->object->get_render_mode();
		
		return $params;
	}

	/**
	 * Renders the frontend display of the display type
	 */
	function index_action($displayed_gallery, $return=FALSE)
	{
		return $this->object->render_partial('photocrati-nextgen_gallery_display#index', array(), $return);
	}

	/**
	 * Returns the url for the JavaScript library required
	 * @return null|string
	 */
	function _get_js_lib_url()
	{
		return NULL;
	}


	/**
	 * Returns the effect HTML code for the displayed gallery
	 * @param type $displayed_gallery
	 */
	function get_effect_code($displayed_gallery)
	{
		$settings = C_NextGen_Settings::get_instance();
		$effect_code = $settings->thumbCode;
		$effect_code = str_replace('%GALLERY_ID%', $displayed_gallery->id(), $effect_code);
		$effect_code = str_replace('%GALLERY_NAME%', $displayed_gallery->id(), $effect_code);
		return $effect_code;
	}


	/**
	 * Adds data to the DOM which is then accessible by a script
	 * @param string $handle
	 * @param string $object_name
	 * @param mixed $object_value
	 * @param bool $define
	 */
	function _add_script_data($handle, $object_name, $object_value, $define=TRUE, $override=FALSE)
	{
		$retval = FALSE;

		// wp_localize_script allows you to add data to the DOM, associated
		// with a particular script. You can even call wp_localize_script
		// multiple times to add multiple objects to the DOM. However, there
		// are a few problems with wp_localize_script:
		//
		// - If you call it with the same object_name more than once, you're
		//   overwritting the first call.
		// - You cannot namespace your objects due to the "var" keyword always
		// - being used.
		//
		// To circumvent the above issues, we're going to use the WP_Scripts
		// object to workaround the above issues
		global $wp_scripts;

		// Has the script been registered or enqueued yet?
		if (isset($wp_scripts->registered[$handle])) {

			// Get the associated data with this script
			$script = &$wp_scripts->registered[$handle];
			$data = &$script->extra['data'];

			// Construct the addition
			$addition = $define ? "\nvar {$object_name} = " . json_encode($object_value) . ';' :
				"\n{$object_name} = " . json_encode($object_value) . ';';

			// Add the addition
			if ($override) {
				$data .= $addition;
				$retval = TRUE;
			}
			else if (strpos($data, $object_name) === FALSE) {
				$data .= $addition;
				$retval = TRUE;
			}

            $script->extra['data'] = $data;
		}

		return $retval;
	}

    // Returns the longest and widest dimensions from a list of entities
    function get_entity_statistics($entities, $named_size, $style_images=FALSE)
    {
        $longest        = $widest = 0;
        $storage        = $this->get_registry()->get_utility('I_Gallery_Storage');
        $image_mapper   = FALSE; // we'll fetch this if needed

        // Calculate longest and
        foreach ($entities as $entity) {

            // Get the image
            $image = FALSE;
            if (isset($entity->pid)) {
                $image = $entity;
            }
            elseif (isset($entity->previewpic)) {
                if (!$image_mapper) $image_mapper = $this->get_registry()->get_utility('I_Image_Mapper');
                $image = $image_mapper->find($entity->previewpic);
            }

            // Once we have the image, get it's dimensions
            if ($image) {
                $dimensions = $storage->get_image_dimensions($image, $named_size);
                if ($dimensions['width']  > $widest)    $widest     = $dimensions['width'];
                if ($dimensions['height'] > $longest)   $longest    = $dimensions['height'];
            }
        }

        // Second loop to style images
        if ($style_images) foreach ($entities as &$entity) {

            // Get the image
            $image = FALSE;
            if (isset($entity->pid)) {
                $image = $entity;
            }
            elseif (isset($entity->previewpic)) {
                if (!$image_mapper) $image_mapper = $this->get_registry()->get_utility('I_Image_Mapper');
                $image = $image_mapper->find($entity->previewpic);
            }

            // Once we have the image, get it's dimension and calculate margins
            if ($image) {
                $dimensions = $storage->get_image_dimensions($image, $named_size);
            }
        }

        return array(
            'entities'  =>  $entities,
            'longest'   =>  $longest,
            'widest'    =>  $widest
        );
    }
}


