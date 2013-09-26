<?php


if (!class_exists('C_NextGen_Settings')) {
	class C_NextGen_Settings {
		static function get_instance()
		{
            return C_Photocrati_Settings_Manager::get_instance();
		}

		static function add_option_handler($klass, $options=array())
		{
			$instance = self::get_instance();
			return $instance->add_option_handler($klass, $options);
		}
	}
}

if (!class_exists('C_NextGen_Global_Settings')) {
	class C_NextGen_Global_Settings extends C_NextGen_Settings {
		static function get_instance()
		{
            if (is_multisite())
                return C_Photocrati_Global_Settings_Manager::get_instance();
            else
                return C_Photocrati_Settings_Manager::get_instance();
		}
	}
}