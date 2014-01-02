<?php

class C_Form extends C_MVC_Controller
{
	static $_instances = array();

	/**
	 * Gets an instance of a form
	 * @param string $context
	 * @return C_Form
	 */
	static function &get_instance($context)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}

	/**
	 * Defines the form
	 * @param string $context
	 */
	function define($context)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Form_Instance_Methods');
		$this->add_mixin('Mixin_Form_Field_Generators');
		$this->implement('I_Form');
	}
}

class Mixin_Form_Instance_Methods extends Mixin
{
	/**
	 * Enqueues any static resources required by the form
	 */
	function enqueue_static_resources()
	{
	}

	/**
	 * Gets a list of fields to render
	 * @return array
	 */
	function _get_field_names()
	{
		return array();
	}

	function get_id()
	{
		return $this->object->context;
	}

	function get_title()
	{
		return $this->object->context;
	}

	/**
	 * Saves the form/model
	 * @param array $attributes
	 * @return type
	 */
	function save_action($attributes=array())
	{
		if ($this->object->has_method('get_model')) {
			return $this->object->get_model()->save($attributes);
		}
		else return TRUE;
	}

	/**
	 * Returns the rendered form
	 */
	function render($wrap = TRUE)
	{
		$fields = array();
		foreach ($this->object->_get_field_names() as $field) {
			$method = "_render_{$field}_field";
			if ($this->object->has_method($method)) {
				$fields[] = $this->object->$method($this->object->get_model());
			}
		}

		return $this->object->render_partial(
            'photocrati-nextgen_admin#form',
            array(
                'fields' => $fields,
                'wrap'   => $wrap
            ),
            TRUE
        );
	}
}

/**
 * Provides some default field generators for forms to use
 */
class Mixin_Form_Field_Generators extends Mixin
{
	function _render_select_field($display_type, $name, $label, $options=array(), $value, $text = '', $hidden = FALSE)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_select',
            array(
                'display_type_name' => $display_type->name,
                'name'    => $name,
                'label'   => _($label),
                'options' => $options,
                'value'   => $value,
                'text'    => $text,
                'hidden'  => $hidden
            ),
            True
        );
    }

    function _render_radio_field($display_type, $name, $label, $value, $text = '', $hidden = FALSE)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_radio',
            array(
                'display_type_name' => $display_type->name,
                'name'   => $name,
                'label'  => _($label),
                'value'  => $value,
                'text'   => $text,
                'hidden' => $hidden
            ),
            True
        );
    }

    function _render_number_field($display_type,
                                  $name,
                                  $label,
                                  $value,
                                  $text = '',
                                  $hidden = FALSE,
                                  $placeholder = '',
                                  $min = NULL,
                                  $max = NULL)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_number',
            array(
                'display_type_name' => $display_type->name,
                'name'  => $name,
                'label' => _($label),
                'value' => $value,
                'text' => $text,
                'hidden' => $hidden,
                'placeholder' => $placeholder,
                'min' => $min,
                'max' => $max
            ),
            True
        );
    }

    function _render_text_field($display_type, $name, $label, $value, $text = '', $hidden = FALSE, $placeholder = '')
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_text',
            array(
                'display_type_name' => $display_type->name,
                'name'  => $name,
                'label' => _($label),
                'value' => $value,
                'text' => $text,
                'hidden' => $hidden,
                'placeholder' => $placeholder
            ),
            True
        );
    }

    function _render_textarea_field($display_type, $name, $label, $value, $text = '', $hidden = FALSE, $placeholder = '')
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_textarea',
            array(
                'display_type_name' => $display_type->name,
                'name'  => $name,
                'label' => _($label),
                'value' => $value,
                'text' => $text,
                'hidden' => $hidden,
                'placeholder' => $placeholder
            ),
            True
        );
    }

    function _render_color_field($display_type, $name, $label, $value, $text = '', $hidden = FALSE)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_color',
            array(
                'display_type_name' => $display_type->name,
                'name'  => $name,
                'label' => _($label),
                'value' => $value,
                'text' => $text,
                'hidden' => $hidden
            ),
            True
        );
    }

	function _render_ajax_pagination_field($display_type)
	{
		return $this->object->_render_radio_field(
			$display_type,
			'ajax_pagination',
			_('Enable AJAX pagination'),
			isset($display_type->settings['ajax_pagination']) ? $display_type->settings['ajax_pagination'] : FALSE
		);
	}
    
    function _render_thumbnail_override_settings_field($display_type)
    {
		$hidden = !(isset($display_type->settings['override_thumbnail_settings']) ? $display_type->settings['override_thumbnail_settings'] : FALSE);

        $override_field = $this->_render_radio_field(
            $display_type,
            'override_thumbnail_settings',
            'Override thumbnail settings',
            isset($display_type->settings['override_thumbnail_settings']) ? $display_type->settings['override_thumbnail_settings'] : FALSE,
			"This does not affect existing thumbnails; overriding the thumbnail settings will create an additional set of thumbnails. To change the size of existing thumbnails please visit 'Manage Galleries' and choose 'Create new thumbnails' for all images in the gallery."
        );

        $dimensions_field = $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/thumbnail_settings',
            array(
                'display_type_name' => $display_type->name,
                'name' => 'thumbnail_dimensions',
                'label'=> _('Thumbnail dimensions'),
                'thumbnail_width' => isset($display_type->settings['thumbnail_width']) ? $display_type->settings['thumbnail_width'] : 0,
                'thumbnail_height'=> isset($display_type->settings['thumbnail_height']) ? $display_type->settings['thumbnail_height'] : 0,
                'hidden' => $hidden ? 'hidden' : '',
                'text' => ''
            ),
            TRUE
        );

        $qualities = array();
        for ($i = 100; $i > 40; $i -= 5) { $qualities[$i] = "{$i}%"; }
        $quality_field = $this->_render_select_field(
            $display_type,
            'thumbnail_quality',
            'Thumbnail quality',
            $qualities,
            isset($display_type->settings['thumbnail_quality']) ? $display_type->settings['thumbnail_quality'] : 100,
            '',
            $hidden
        );

        $crop_field = $this->_render_radio_field(
            $display_type,
            'thumbnail_crop',
            'Thumbnail crop',
            isset($display_type->settings['thumbnail_crop']) ? $display_type->settings['thumbnail_crop'] : FALSE,
            '',
            $hidden
        );

        $watermark_field = $this->_render_radio_field(
            $display_type,
            'thumbnail_watermark',
            'Thumbnail watermark',
            isset($display_type->settings['thumbnail_watermark']) ? $display_type->settings['thumbnail_watermark'] : FALSE,
            '',
            $hidden
        );

        $everything = $override_field . $dimensions_field . $quality_field . $crop_field . $watermark_field;

        return $everything;
    }
    

    /**
     * Renders the thumbnail override settings field(s)
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_image_override_settings_field($display_type)
    {
		$hidden = !(isset($display_type->settings['override_image_settings']) ? $display_type->settings['override_image_settings'] : FALSE);

        $override_field = $this->_render_radio_field(
            $display_type,
            'override_image_settings',
            'Override image settings',
            isset($display_type->settings['override_image_settings']) ? $display_type->settings['override_image_settings'] : 0,
			'Overriding the image settings will create an additional set of images'
        );

        $qualities = array();
        for ($i = 100; $i > 40; $i -= 5) { $qualities[$i] = "{$i}%"; }
        $quality_field = $this->_render_select_field(
            $display_type,
            'image_quality',
            'Image quality',
            $qualities,    
            $display_type->settings['image_quality'],
            '',
            $hidden
        );

        $crop_field = $this->_render_radio_field(
            $display_type,
            'image_crop',
            'Image crop',
            $display_type->settings['image_crop'],
            '',
            $hidden
        );

        $watermark_field = $this->_render_radio_field(
            $display_type,
            'image_watermark',
            'Image watermark',
            $display_type->settings['image_watermark'],
            '',
            $hidden
        );

        $everything = $override_field . $quality_field . $crop_field . $watermark_field;

        return $everything;
    }

    /**
     * Renders a pair of fields for width and width-units (px, em, etc)
     *
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_width_and_unit_field($display_type)
    {
        return $this->object->render_partial(
            'photocrati-nextgen_admin#field_generator/nextgen_settings_field_width_and_unit',
            array(
                'display_type_name' => $display_type->name,
                'name' => 'width',
                'label' => 'Gallery width',
                'value' => $display_type->settings['width'],
                'text' => 'An empty or "0" setting will make the gallery full width',
                'placeholder' => '(optional)',
                'unit_name' => 'width_unit',
                'unit_value' => $display_type->settings['width_unit'],
                'options' => array('px' => 'Pixels', '%' => 'Percent')
            ),
            TRUE
        );
    }

    function _get_aspect_ratio_options()
    {
        return array(
            'first_image' => __('First Image', 'nggallery'),
            'image_average' => __('Average', 'nggallery'),
            '1.5'   => '3:2 [1.5]',
            '1.333' => '4:3 [1.333]',
            '1.777' => '16:9 [1.777]',
            '1.6'   => '16:10 [1.6]',
            '1.85'  => '1.85:1 [1.85]',
            '2.39'  => '2.39:1 [2.39]',
            '1.81'  => '1.81:1 [1.81]',
            '1'     => '1:1 (Square) [1]'
        );
    }
}
