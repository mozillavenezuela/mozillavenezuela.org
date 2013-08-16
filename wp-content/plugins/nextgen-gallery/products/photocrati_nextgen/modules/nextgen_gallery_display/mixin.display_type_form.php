<?php

class Mixin_Display_Type_Form extends Mixin
{
	var $_model = null;
	

  function initialize()
  {
  	$this->object->implement('I_Display_Type_Form');
  }
  
	/**
	 * Returns the name of the display type. Sub-class should override
	 * @throws Exception
	 * @returns string
	 */
	function get_display_type_name()
	{
		throw new Exception(__METHOD__." not implemented");
	}

	/**
	 * Returns the model (display type) used in the form
	 * @return stdClass
	 */
	function get_model()
	{
		if ($this->_model == null)
		{
			$mapper = $this->get_registry()->get_utility('I_Display_Type_Mapper');
			$this->_model = $mapper->find_by_name($this->object->get_display_type_name(), TRUE);
		}
		
		return $this->_model;
	}

	/**
	 * Returns the title of the form, which is the title of the display type
	 * @returns string
	 */
	function get_title()
	{
		return $this->object->get_model()->title;
	}
        
        
        /**
         * Saves the settings for the display type
         * @param array $attributes
         * @return boolean
         */
        function save_action($attributes=array())
        {
            return $this->object->get_model()->save(array('settings'=>$attributes));
        }
}
