<?php

class C_Form_Manager extends C_Component
{
	static $_instances	= array();
	var $_forms			= array();
	/**
	 * Returns an instance of the form manager
	 * @returns C_Form_Manager
	 */
	static function &get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}

	/**
	 * Defines the instance
	 * @param mixed $context
	 */
	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Form_Manager');
		$this->implement('I_Form_Manager');
	}
}

class Mixin_Form_Manager extends Mixin
{
	/**
	 * Adds one or more
	 * @param type $type
	 * @param type $form_names
	 * @return type
	 */
	function add_form($type, $form_names)
	{
		if (!isset($this->object->_forms[$type])) {
			$this->object->_forms[$type] = array();
		}

		if (!is_array($form_names)) $form_names= array($form_names);
		foreach ($form_names as $form) $this->object->_forms[$type][] = $form;

		return $this->object->get_form_count($type);
	}

	/**
	 * Alias for add_form() method
	 * @param string $type
	 * @param string|array $form_names
	 * @return int
	 */
	function add_forms($type, $form_names)
	{
		return $this->object->add_form($type, $form_names);
	}

	/**
	 * Removes one or more forms of a particular type
	 * @param string $type
	 * @param string|array $form_names
	 * @return int	number of forms remaining for the type
	 */
	function remove_form($type, $form_names)
	{
		$retval = 0;
		if (isset($this->object->_forms[$type])) {
			foreach ($form_names as $form) {
				if (($index = array_search($form, $this->object->_forms[$type])))
					unsset($this->object->_forms[$type][$index]);
			}
			$retval = $this->object->get_form_count($type);
		}

		return $retval;
	}

	/**
	 * Alias for remove_form() method
	 * @param string $type
	 * @param string|array $form_names
	 * @return int
	 */
	function remove_forms($type, $form_names)
	{
		return $this->object->remove_form($type, $form_names);
	}

	/**
	 * Gets known form types
	 * @return type
	 */
	function get_known_types()
	{
		return array_keys($this->object->_forms);
	}


	/**
	 * Gets forms of a particular type
	 * @param string $type
	 * @return array
	 */
	function get_forms($type, $instantiate=FALSE)
	{
		$retval = array();
		if (isset($this->object->_forms[$type])) {
			if (!$instantiate) $retval = $this->object->_forms[$type];
			else foreach ($this->object->_forms[$type] as $context) {
				$retval[] = $this->get_registry()->get_utility('I_Form', $context);
			}
		}
		return $retval;
	}

	/**
	 * Gets the number of forms registered for a particular type
	 * @param string $type
	 * @return int
	 */
	function get_form_count($type)
	{
		$retval = 0;
		if (isset($this->object->_forms[$type])) {
			$retval = count($this->object->_forms[$type]);
		}
		return $retval;
	}

	/**
	 * Gets the index of a particular form
	 * @param string $type
	 * @param string $name
	 * @return FALSE|int
	 */
	function get_form_index($type, $name)
	{
		$retval = FALSE;
		if ($this->object->get_form_count($type) > 0) {
			$retval = array_search($name, $this->object->_forms[$type]);
		}
		return $retval;
	}

	/**
	 * Adds one or more forms before a form already registered
	 * @param string $type
	 * @param string $before
	 * @param string|array $form_names
	 * @param int $offset
	 * @return int
	 */
	function add_form_before($type, $before, $form_names, $offset=0)
	{
		$retval		= 0;
		$index		= FALSE;
		$use_add	= FALSE;

		// Append the forms
		if ($this->object->get_form_count($type) == 0) $use_add = TRUE;
		else if (($index = $this->object->get_form_index($type, $name)) == FALSE) $use_add = FALSE;
		if ($use_add) $this->object->add_forms($type, $form_names);
		else {
			$before = array_slice($this->object->get_forms($type), 0, $offset);
			$after	= array_slice($this->object->get_forms($type), $offset);
			$this->object->_forms[$type] = array_merge($before, $form_names, $after);
			$retval = $this->object->get_form_count($type);
		}

		return $retval;
	}

	/**
	 * Adds one or more forms after an existing form
	 * @param string $type
	 * @param string $after
	 * @param string|array $form_names
	 * @return int
	 */
	function add_form_after($type, $after, $form_names)
	{
		return $this->object->add_form_before($type, $after, $form_names, 1);
	}
}