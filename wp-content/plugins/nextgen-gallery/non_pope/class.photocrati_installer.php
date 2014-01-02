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
				C_NextGen_Settings::get_instance()->destroy();
                C_NextGen_Global_Settings::get_instance()->destroy();
			}
		}

		static function update($reset=FALSE)
		{
			$local_settings     = C_NextGen_Settings::get_instance();
            $global_settings    = C_NextGen_Global_Settings::get_instance();

            // This is a specific hack/work-around/fix and can probably be removed sometime after 2.0.20's release
            //
            // NextGen 2x was not multisite compatible until 2.0.18. Users that upgraded before this
            // will have nearly all of their settings stored globally (network wide) in wp_sitemeta. If
            // pope_module_list (which should always be a local setting) exists site-wide we wipe the current
            // global ngg_options and restore from defaults. This should only ever run once.
            if (is_multisite() && isset($global_settings->pope_module_list))
            {
                // Setting this to TRUE will wipe current settings for display types, but also
                // allows the display type installer to run correctly
                $reset = TRUE;

                $settings_installer = new C_NextGen_Settings_Installer();
                $global_defaults = $settings_installer->get_global_defaults();

                // Preserve the network options we honor by restoring them after calling $global_settings->reset()
                $global_settings_to_keep = array();
                foreach ($global_defaults as $key => $val) {
                    $global_settings_to_keep[$key] = $global_settings->$key;
                }

                // Resets internal options to an empty array
                $global_settings->reset();

                // Restore the defaults, then our saved values. This must be done again later because
                // we've set $reset to TRUE.
                $settings_installer->install_global_settings();
                foreach ($global_settings_to_keep as $key => $val) {
                    $global_settings->$key = $val;
                }
            }

            $last_module_list    = $reset ? array() : $local_settings->get('pope_module_list', array());
			$current_module_list = self::_generate_module_info();

            if (count(($modules = array_diff($current_module_list, $last_module_list))) > 0)
            {
				// The cache should be flushed
				C_Photocrati_Cache::flush();

				// Remove all NGG created cron jobs
				self::refresh_cron();

				// Delete auto-update cache
				update_option('photocrati_auto_update_admin_update_list', null);
				update_option('photocrati_auto_update_admin_check_date', '');

				// Other Pope applications might be loaded, and therefore
				// all singletons should be destroyed, so that they can be
				// adapted as necessary. For now, we'll just assume that the factory
				// is the only singleton that will be used by other Pope applications
				C_Component_Factory::$_instances = array();

				foreach ($modules as $module_name) {
					if (($handler = self::get_handler_instance(array_shift(explode('|', $module_name))))) {
						if (method_exists($handler, 'install'))
                            $handler->install($reset);
					}
				}

				// Update the module list
				$local_settings->set('pope_module_list', $current_module_list);

                // NOTE & TODO: if the above section that declares $global_settings_to_keep is removed this should also
                // Since a hard-reset of the settings was forced we must again re-apply our previously saved values
                if (isset($global_settings_to_keep)) {
                    foreach ($global_settings_to_keep as $key => $val) {
                        $global_settings->$key = $val;
                    }
                }

				// Save any changes settings
				$global_settings->save();
				$local_settings->save();
            }

            // Another workaround to an issue caused by NextGen's lack of multisite compatibility. It's possible
            // the string substitation wasn't performed, so if a '%' symbol exists in gallerypath we reset it. It's
            // another db call, but again this should only ever run once.
            //
            // Remove this when removing the above reset-global-settings code
            if (strpos($local_settings->gallerypath, '%'))
            {
                $settings_installer = new C_NextGen_Settings_Installer();
                $local_settings->gallerypath = $settings_installer->gallerypath_replace($global_settings->gallerypath);
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

		static function refresh_cron()
		{
			@ini_set('memory_limit', -1);

			// Remove all cron jobs created by NextGEN Gallery
			$cron = _get_cron_array();
			if (is_array($cron)) {
				foreach ($cron as $timestamp => $job) {
					if (is_array($job)) {
						unset($cron[$timestamp]['ngg_delete_expired_transients']);
						if (empty($cron[$timestamp])) {
							unset($cron[$timestamp]);
						}
					}
				}
			}
			_set_cron_array($cron);
		}
	}
}