<?php

class C_Frame_Event_Publisher extends C_Component
{
	static $_instances	= array();
	var $setting_name	= NULL;

	function define($context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_Frame_Event_Publisher');
		$this->implement('I_Frame_Event_Publisher');
	}

	function initialize()
	{
		parent::initialize();
		$this->setting_name = C_NextGen_Settings::get_instance()->frame_communication_option_name;
	}

	/**
	 * Gets an instance of the publisher
	 * @param string $context
	 * @return C_Frame_Event_Publisher
	 */
	static function get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}
}

class Mixin_Frame_Event_Publisher extends Mixin
{
	/**
	 * Encodes data for a setting
	 * @param array $data
	 * @return string
	 */
	function _encode($data)
	{
		return rawurlencode(json_encode($data));
	}

	/**
	 * Decodes data from a setting
	 * @param string $data
	 * @return array
	 */
	function _decode($data)
	{
		return (array)json_decode(rawurldecode($data));
	}

	/**
	 * Adds a setting to the frame events
	 * @param type $data
	 * @return type
	 */
	function add_event($data)
	{
		$id			= md5(serialize($data));
		$data['context'] = $this->object->context;

		$write_cookie = TRUE;
		if (defined('XMLRPC_REQUEST')) {
			$write_cookie = XMLRPC_REQUEST == FALSE;
		}

		if ($write_cookie) {
			setrawcookie($this->object->setting_name.'_'.$id,$this->object->_encode($data));
		}

		return $data;
	}
}
