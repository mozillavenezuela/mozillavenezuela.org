<?php

if (!class_exists('C_Photocrati_Installer'))
{
	class C_Photocrati_Installer
	{
		static $_instance = NULL;
		static function get_instance()
		{
			if (is_null(self::$_instance)) {
				$klass = get_class();
				self::$_instance = new $klass();
			}
			return self::$_instance;
		}


		/**
		 * Each product and module will register it's own handler (a class, with an install() and uninstall() method)
		 * to be used for install/uninstall routines
		 * @param $name
		 * @param $handler
		 */
		static function add_handler($name, $handler)
		{
			self::get_instance()->_installers[$name] = $handler;
		}

		/**
		 * Gets an instance of an installation handler
		 * @param $name
		 * @return mixed
		 */
		static function get_handler_instance($name)
		{
			$installers = $handler = self::get_instance()->_installers;
			if (isset($installers[$name])) {
				$klass = $installers[$name];
				return new $klass;
			}
			else return NULL;
		}


		/**
		 * Uninstalls a product
		 * @param $product
		 * @param bool $hard
		 * @return mixed
		 */
		static function uninstall($product, $hard=FALSE)
		{
			$handler = self::get_handler_instance($product);
			if (method_exists($handler, 'uninstall')) return $handler->uninstall($hard);

			if ($hard) {
				C_NextGen_Global_Settings::get_instance()->destroy();
				C_NextGen_Settings::get_instance()->destroy();
			}
		}

		static function update($reset=FALSE)
		{
			$global_settings		= C_NextGen_Global_Settings::get_instance();
			$local_settings			= C_NextGen_Settings::get_instance();
			$last_module_list		= $reset ? array() : $global_settings->get('pope_module_list', array());
			$current_module_list	= self::_generate_module_info();

			if (count(($modules = array_diff($current_module_list, $last_module_list)))>0) {

				// The cache should be flushed
				C_Photocrati_Cache::flush();

				// Delete auto-update cache
				update_option('photocrati_auto_update_admin_update_list', null);
				update_option('photocrati_auto_update_admin_check_date', '');

				foreach ($modules as $module_name) {
					if (($handler = self::get_handler_instance(array_shift(explode('|', $module_name))))) {
						if (method_exists($handler, 'install')) $handler->install($reset);
					}
				}

				// Update the module list
				$global_settings->set('pope_module_list', $current_module_list);

				// Save any changes settings
				$global_settings->save();
				$local_settings->save();
			}
		}

		static function _generate_module_info()
		{
			$retval = array();
			$registry = C_Component_Registry::get_instance();
			foreach ($registry->get_module_list() as $module_id) {
				$module_version = $registry->get_module($module_id)->module_version;
				$retval[$module_id] = "{$module_id}|{$module_version}";
			}
			return $retval;
		}
	}
}