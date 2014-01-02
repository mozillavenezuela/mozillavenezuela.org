<?php

class Mixin_NextGen_Table_Extras extends Mixin
{
	const CUSTOM_POST_NAME = __CLASS__;

	function initialize()
	{
		// Each record in a NextGEN Gallery table has an associated custom post in the wp_posts table
		$this->object->_custom_post_mapper = new C_CustomPost_DataMapper_Driver($this->object->get_object_name());
		$this->object->_custom_post_mapper->set_model_factory_method('extra_fields');
	}

	/**
	 * Defines a column for the mapper
	 * @param $name
	 * @param $data_type
	 * @param null $default_value
	 * @param bool $extra
	 */
	function define_column($name, $data_type, $default_value=NULL, $extra=FALSE)
	{
		$this->call_parent('define_column', $name, $data_type, $default_value);
		if ($extra) {
			$this->object->_columns[$name]['extra'] = TRUE;
		}
		else $this->object->_columns[$name]['extra'] = FALSE;
	}

	/**
	 * Gets a list of all the extra columns defined for this table
	 * @return array
	 */
	function get_extra_columns()
	{
		$retval = array();

		foreach ($this->object->_columns as $key => $properties) {
			if ($properties['extra']) $retval[] = $key;
		}

		return $retval;
	}

	/**
	 * Adds a column to the database
	 * @param $column_name
	 * @param $datatype
	 * @param null $default_value
	 */
	function _add_column($column_name, $datatype, $default_value=NULL)
	{
		$skip = FALSE;
		if (isset($this->object->_columns[$column_name]) and $this->object->_columns[$column_name]['extra']) {
			$skip = TRUE;
		}
		if (!$skip) $this->call_parent('_add_column', $column_name, $datatype, $default_value);
	}

	function create_custom_post_entity($entity)
	{
		$custom_post_entity = new stdClass;

		// If the custom post entity already exists then it needs
		// an ID
		if (isset($entity->custom_post_id)) $custom_post_entity->ID = $entity->custom_post_id;

		// If a property isn't a column for the table, then
		// it belongs to the custom post record
		foreach (get_object_vars($entity) as $key => $value) {
			if (!$this->object->has_column($key)) {
				unset($entity->$key);
				if ($this->object->has_defined_column($key) && $key != $this->object->get_primary_key_column())
					$custom_post_entity->$key = $value;
			}
		}

		// Used to help find these type of records
		$custom_post_entity->post_name = self::CUSTOM_POST_NAME;

		return $custom_post_entity;
	}

	/**
	 * Gets the name of the WordPress option that holds the ID of the associated custom post ID record
	 * @param $entity
	 * @return string
	 */
	function _get_option_name($entity)
	{
		$primary_key = $this->object->get_primary_key_column();
		return $this->get_table_name().'_'.$entity->$primary_key;
	}

	/**
	 * Creates a new record in the custom table, as well as a custom post record
	 * @param $entity
	 */
	function _create($entity)
	{
		$retval = FALSE;
		$custom_post_entity = $this->create_custom_post_entity($entity);

		// Try persisting the custom post type record first
		if (($custom_post_id = $this->object->_custom_post_mapper->save($custom_post_entity))) {

			// Try saving the custom table record. If that fails, then destroy the previously
			// created custom post type record
			if (!($retval = $this->call_parent('_create', $entity))) {
				$this->object->_custom_post_mapper->destroy($custom_post_id);
			}

			// Add the custom post id property
			else {
				$option_name = $this->_get_option_name($entity);
				update_option($option_name, $custom_post_id);
				$entity->custom_post_id = $custom_post_id;
			}
		}

		return $retval;
	}

	// Updates a custom table record and it's associated custom post type record in the database
	function _update($entity)
	{
		$retval = FALSE;
		$custom_post_entity = $this->create_custom_post_entity($entity);
		$custom_post_id = $this->object->_custom_post_mapper->save($custom_post_entity);
		$retval = $this->call_parent('_update', $entity);
		$entity->custom_post_id = $custom_post_id;
		update_option($this->_get_option_name($entity), $custom_post_id);
		foreach ($this->get_extra_columns() as $key) {
			if (isset($custom_post_entity->$key)) $entity->$key = $custom_post_entity->$key;
		}

		return $retval;
	}

	function destroy($entity)
	{
		if (isset($entity->custom_post_id)) {
			wp_delete_post($entity->custom_post_id, TRUE);
			delete_option($this->_get_option_name($entity));
		}

		return $this->call_parent('destroy', $entity);
	}

	/**
	 * Gets the generated query
	 */
	function get_generated_query()
	{
		// Add extras column
		if ($this->object->is_select_statement()) {
			global $wpdb;
			$table_name = $this->object->get_table_name();
			$primary_key = "{$table_name}.{$this->object->get_primary_key_column()}";
			$this->object->group_by($primary_key);
			$sql = $this->call_parent('get_generated_query');
			$from = 'FROM `'.$this->object->get_table_name().'`';
			$sql = str_replace('FROM', ", `{$wpdb->options}`.`option_value` AS 'custom_post_id', GROUP_CONCAT(CONCAT_WS('@@', meta_key, meta_value)) AS 'extras' FROM", $sql);
			$sql = str_replace($from, "{$from} LEFT OUTER JOIN `{$wpdb->options}` ON `{$wpdb->options}`.option_name = CONCAT('{$table_name}_', {$primary_key}) LEFT OUTER JOIN `{$wpdb->postmeta}` ON `{$wpdb->postmeta}`.`post_id` = `{$wpdb->options}`.`option_value` ", $sql);
		}
		else $sql = $this->call_parent('get_generated_query');

		return $sql;
	}

	function _convert_to_entity($entity)
	{
		// Add extra columns to entity
		if (isset($entity->extras)) {
			$extras = $entity->extras;
			unset($entity->extras);
			foreach (explode(',', $extras) as $extra) {
				if ($extra) {
					list($key, $value) = explode('@@', $extra);
					if ($this->object->has_defined_column($key) && !isset($entity->key)) $entity->$key = $value;
				}
			}
		}

		// Cast custom_post_id as integer
		if (isset($entity->custom_post_id)) {
			$entity->custom_post_id = intval($entity->custom_post_id);
		}
		else $entity->custom_post_id = 0;

		$retval = $this->call_parent('_convert_to_entity', $entity);

		return $entity;
	}
}