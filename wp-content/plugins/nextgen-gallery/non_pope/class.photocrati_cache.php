<?php

class C_Photocrati_Cache
{
	static $enabled       = TRUE;
	static $do_not_lookup = FALSE;
	static $force_update  = FALSE;
	static $hits		  = 0;
	static $_instances	  = array();
	public $group	  	  = NULL;

	/**
	 * Gets an instance of the Cache
	 * @return C_Photocrati_Cache
	 */
	static function &get_instance($group=NULL)
	{
		if (!$group) $group = 'ngg_cache_';
		if (substr($group, -1) != '_') $group .= '_';
		if (!isset(self::$_instances[$group])) {
			$klass = get_class();
			self::$_instances[$group] = new $klass($group);
		}

		return self::$_instances[$group];
	}

	/**
	 * Create a new cache for the specified group
	 * @param $group
	 */
	function __construct($group)
	{
		$this->group = $group;
	}

	/**
	 * Gets an item from the cache
	 * @param $key
	 * @param null $default
	 * @return mixed
	 */
	static function get($key, $default=NULL, $group=NULL)
	{
		return self::get_instance($group)->lookup($key, $default);
	}

	/**
	 * Caches an item
	 * @param $key
	 * @param null $value
	 * @return bool|int
	 */
	static function set($key, $value=NULL, $group=NULL, $ttl=3600)
	{
		return self::get_instance($group)->update($key, $value, $ttl);
	}

	/**
	 * Removes an item from the cache
	 * @param $key
	 */
	static function remove($key, $group=NULL)
	{
		return self::get_instance($group)->delete($key);
	}

	/**
	 * Generate a unique key from params
	 * @param $params
	 * @return string
	 */
	static function generate_key($params)
	{
		if (!self::$enabled) return NULL;
		if (is_object($params)) $params = (array) $params;
		if (is_array($params)) {
			foreach ($params as &$param) $param = json_encode($param);
			$params = implode('', $params);
		}

		return md5($params);
	}

	/**
	 * Flush the entire cache
	 */
	static function flush($group=NULL)
	{
		$retval = 0;

		if (self::$enabled) {

			// Delete all caches
			if ($group == 'all') {
				foreach (self::$_instances as $cache) {
					$retval += self::flush($cache->group);
				}
			}

			// Delete items from a single cache in particular
			else {
				foreach (self::get_key_list($group) as $key) {
					self::delete($key, FALSE, $group);
				}

				// Delete list of cached items
				global $wpdb;
				$cache = self::get_instance($group);
				$sql = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '%'.$cache->group.'%');
				$retval = $wpdb->query($sql);
			}
		}

		return $retval;
	}

	static function get_key_list($group=NULL)
	{
		global $wpdb;

		$cache = self::get_instance($group);
		$sql = $wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", '%'.$cache->group.'%');
		return $wpdb->get_col($sql);
	}


	/**
	 * Gets an item using a particular key
	 * @param $key
	 * @param $default
	 * @return mixed
	 */
	function lookup($key, $default=NULL)
	{
		$retval = $default;

		if (self::$enabled && self::$do_not_lookup === FALSE) {
			if (is_array($key)) $key = self::generate_key($key);
			if (!($retval = get_transient($key))) $retval = $default;
		}

		return $retval;
	}

	/**
	 * Set an item in the cache using a particular key
	 * @param $key
	 * @param $value
	 * @return bool|int
	 */
	function update($key, $value, $ttl=3600)
	{
		$retval = FALSE;
		if (self::$enabled) {
			if (is_array($key)) $key = self::generate_key($key);
			if (self::$force_update OR $this->lookup($key, FALSE) === FALSE) {
				set_transient($key, $value, $ttl);
				update_option($this->group.$key, 1);
				$retval = $key;
			}
		}
		return $retval;
	}

	function delete($key, $delete_ack=TRUE)
	{
		if (self::$enabled) {
			delete_transient($key);
			if ($delete_ack) {
				global $wpdb;
				$sql = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $this->group.$key);
				$wpdb->query($sql);
			}
			return TRUE;
		}
		else return FALSE;
	}
}