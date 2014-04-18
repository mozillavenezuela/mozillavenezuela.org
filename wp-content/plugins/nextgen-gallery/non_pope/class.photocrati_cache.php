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
	static function set($key, $value=NULL, $group=NULL, $ttl=NULL)
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
	static function flush($group=NULL, $expired_only=FALSE)
	{
        ini_set('memory_limit', -1);
		$retval = 0;

		if (self::$enabled) {

			// Delete all caches
			if ($group == 'all') {
				foreach (self::$_instances as $cache) {
					$retval += self::flush($cache->group, $expired_only);
				}
			}

			// Delete items from a single cache in particular
			else {
				$cache = self::get_instance($group);

				// Determine if the object cache is external, and not stored in the DB
				// If it's external, we have to delete each transient, one by one
				global $_wp_using_ext_object_cache, $wpdb;
				if ($_wp_using_ext_object_cache) {
					$keys = ($expired_only ? self::get_expired_key_list($group) : self::get_key_list($group));
					foreach ($keys as $key) $cache->delete($key, FALSE);
					$sql = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", "%%{$cache->group}%%");
					if ($expired_only) $sql .= " AND option_value < ".time();
					$retval = $wpdb->query($sql);
				}

				// Transients are stored in the database
				else {
					$keys = ($expired_only ? self::get_expired_key_list($group) : self::get_key_list($group));
					if ($keys) {
						$all_keys = array();
						foreach ($keys as $value) {
							$all_keys[] = "'{$cache->group}{$value}'";
							$all_keys[] = "'_transient_timeout_{$value}'";
							$all_keys[] = "'_transient_{$value}'";
						}

						// Determine the maximum packet size for the MySQL server
						$max_packet_size = 1000000; // 1 MB
						if ($row = $wpdb->get_row("SHOW VARIABLES LIKE 'max_allowed_packet'")) {
							$max_packet_size = intval($row->Value);
						}
						$precision = -6;
						if ($max_packet_size <1000000)	$precision = -5;
						if ($max_packet_size <100000)	$precision = -4;
						if ($max_packet_size <10000)	$precision = -3;
						if ($max_packet_size <1000)		$precision = -2;

                        if (version_compare(PHP_VERSION, '5.3.0') >= 0)
                            $max_packet_size = round($max_packet_size, $precision, PHP_ROUND_HALF_DOWN);
                        else
                            $max_packet_size = round($max_packet_size, $precision);

						// Generate DELETE queries up to $max_packet_size
						$keys = array();
						$average_key_size = strlen($all_keys[0])+15;
						$count = 1000; // 1 KB buffer
						while (($key = array_pop($all_keys))) {

							if (($count + $average_key_size) < $max_packet_size) {
								$keys[] = $key;
								$count += $average_key_size;
							}
							else {
								$keys = implode(',', $keys);
								$sql = "DELETE FROM {$wpdb->options} WHERE option_name IN (". $keys. ')';
								if (strlen($sql) > $max_packet_size) error_log("Delete transient query larger than max_allowed_packet for MySQL");
								else $retval += $wpdb->query($sql);
								$count = 1000;
								$keys = array();
							}
						}

                        // If the number of keys to delete is less than the max packet size, then we should still
                        // delete the records
                        if (!$retval && $keys) {
                            $keys = implode(',', $keys);
                            $sql = "DELETE FROM {$wpdb->options} WHERE option_name IN (". $keys. ')';
                            if (strlen($sql) > $max_packet_size) error_log("Delete transient query larger than max_allowed_packet for MySQL");
                            else $retval += $wpdb->query($sql);
                        }
					}
				}
			}
		}

		return $retval;
	}

	static function get_key_list($group=NULL, $strip_group_name=TRUE, $expired_only=FALSE)
	{
		global $wpdb;

		$cache = self::get_instance($group);

		$sql = '';
		if ($strip_group_name) {
			$sql = $wpdb->prepare(
				"SELECT REPLACE(option_name, %s, '') FROM {$wpdb->options} WHERE option_name LIKE %s",
				$cache->group, '%'.$cache->group.'%'
			);
		}
		else {
			$sql = $wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%'.$cache->group.'%'
			);
		}

		if ($expired_only) $sql .= " AND option_value < ".time();

		return $wpdb->get_col($sql);
	}

	static function get_expired_key_list($group=NULL, $strip_group_name=TRUE)
	{
		return self::get_key_list($group, $strip_group_name, TRUE);
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
	function update($key, $value, $ttl=NULL)
	{
        if (!$ttl) $ttl = PHOTOCRATI_CACHE_TTL;

		$retval = FALSE;
		if (self::$enabled) {
			if (is_array($key)) $key = self::generate_key($key);
			if (self::$force_update OR $this->lookup($key, FALSE) === FALSE) {
				set_transient($key, $value, $ttl);
                delete_option($this->group.$key);
				add_option($this->group.$key, time()+$ttl, NULL, 'no');
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
