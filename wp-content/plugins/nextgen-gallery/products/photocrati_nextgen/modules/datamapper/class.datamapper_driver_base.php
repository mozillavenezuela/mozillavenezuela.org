<?php

/**
 * Thrown when an entity does not exist
 */
class E_EntityNotFoundException extends E_NggErrorException
{

}


class E_ColumnsNotDefinedException extends E_NggErrorException
{

}

/**
 * Thrown when an invalid data type is used as an entity, such as an associative
 * array which is not yet supported due to a problem with references and the
 * call_user_func_array() function.
 */
class E_InvalidEntityException extends E_NggErrorException
{
	function __construct($message_or_previous=FALSE, $code=0, $previous=NULL)
	{
		// We don't know if we have been passed a message yet or not
		$message = FALSE;

		// Determine if the first parameter is a string or exception
		if ($message_or_previous) {
			if (is_string($message_or_previous))
				$message = $message_or_previous;
			else {
				$previous = $message_or_previous;
			}
		}

		// If no message was provided, create a default message
		if (!$message) {
			$message =  "Invalid data type used for entity. Please use stdClass
				or a subclass of C_DataMapper_Model. Arrays will be supported in
				the future.";
		}
		parent::__construct($message, $code);
	}
}


class Mixin_DataMapper_Driver_Base extends Mixin
{
	/**
	 * Used to clean column or table names in a SQL query
	 * @param string $val
	 * @return string
	 */
	function _clean_column($val)
	{
		return str_replace(
			array(';', "'", '"', '`'),
			array(''),
			$val
		);
	}

	/**
	 * Notes that a particular columns is serialized, and should be unserialized when converted to an entity
	 * @param $column
	 */
	function add_serialized_column($column)
	{
		$this->object->_serialized_columns[] = $column;
	}

	function unserialize_columns($object)
	{
		foreach ($this->object->_serialized_columns as $column) {
			if (isset($object->$column) && is_string($object->$column)) {
				$object->$column = $this->unserialize($object->$column);
			}
		}
	}

	/**
	 * Serializes the data
	 * @param mixed $value
	 * @return string
	 */
	function serialize($value)
	{
		//Using json_encode here because PHP's serialize is not Unicode safe
		return base64_encode(json_encode($value));
	}


	/**
	 * Unserializes data using our proprietary format
	 * @param string $value
	 * @return mixed
	 */
	function unserialize($value)
	{
		$retval = NULL;

		if (is_string($value))
		{
			$retval = stripcslashes($value);

			if (strlen($value) > 1)
			{
				//Using json_decode here because PHP's unserialize is not Unicode safe
				$retval = json_decode(base64_decode($retval), TRUE);

				// JSON Decoding failed. Perhaps it's PHP serialized data?
				if ($retval === NULL) {
					$er = error_reporting(0);
					$retval = unserialize($value);
					error_reporting($er);
				}
			}
		}

		return $retval;
	}


	/**
	 * Finds a partiular entry by id
	 * @param int|stdClass|C_DataMapper_Model $entity
	 * @return C_DataMapper_Entity
	 */
	function find($entity, $model=FALSE)
	{
        $retval = NULL;

        // Get primary key of the entity
		$pkey = $this->object->get_primary_key_column();
		if (!is_numeric($entity)) {
            $entity = isset($entity->$pkey) ? intval($entity->$pkey) : FALSE;
        }

        // If we have an entity ID, then get the record
        if ($entity) {
            $results = $this->object->select()->where_and(
                array("{$pkey} = %d", $entity)
            )->limit(1,0)->run_query();

            if ($results) $retval = $model ? $this->object->convert_to_model($results[0]) :  $results[0];
        }

        return $retval;
	}

	/**
	 * Fetches the first row
	 * @param array $conditions
	 * @return C_DataMapper_Entity
	 */
	function find_first($conditions=array(), $model=FALSE)
	{
		$results = $this->object->select()->where_and($conditions)->limit(1,0)->run_query();
		if ($results)
			return $model? $this->object->convert_to_model($results[0]) : $results[0];
		else
			return NULL;
	}


	/**
	 * Queries all rows
	 * @param array $conditions
	 * @return array
	 */
	function find_all($conditions=array(), $model=FALSE)
	{
		// Sometimes users will forget that the first parameter is conditions, and think it's $model instead
		if ($conditions === TRUE)
        {
			$conditions = array();
			$model = TRUE;
		}

        if ($conditions === FALSE)
        {
            $conditions = array();
            $model = FALSE;
        }

		$results = $this->object->select()->where_and($conditions)->run_query();
		if ($results && $model)
        {
			foreach ($results as &$r) {
				$r = $this->object->convert_to_model($r);
			}
		}

        return $results;
	}


	/**
	 * Filters the query using conditions:
	 * E.g.
	 *		array("post_title = %s", "Foo")
	 *		array(
	 *			array("post_title = %s", "Foo"),
	 *
	 *		)
	 */
	function where_and($conditions=array())
	{
		return $this->object->_where($conditions, 'AND');
	}

	function where_or($conditions=array())
	{
		return $this->object->where($conditions, 'OR');
	}


	function where($conditions=array())
	{
		return $this->object->_where($conditions, 'AND');
	}


	/** Parses the where clauses
	 * They could look like the following:
	 *
	 * array(
	 *  "post_id = 1"
	 *  array("post_id = %d", 1),
	 * )
	 *
	 * or simply "post_id = 1"
	 * @param array|string $conditions
	 * @param string $operator
	 * @return ExtensibleObject
	 */
	function _where($conditions=array(), $operator)
	{
		$where_clauses = array();

		// If conditions is not an array, make it one
		if (!is_array($conditions)) $conditions = array($conditions);
		elseif (!empty($conditions) && !is_array($conditions[0])) {
			// Just a single condition was passed, but with a bind
			$conditions = array($conditions);
		}

		// Iterate through each condition
		foreach ($conditions as $condition) {
			if (is_string($condition)) {
				$clause = $this->object->_parse_where_clause($condition);
				if ($clause) $where_clauses[] = $clause;
			}
			else {
				$clause = array_shift($condition);
				$clause = $this->object->_parse_where_clause($clause, $condition);
				if ($clause) $where_clauses[] = $clause;
			}
		}

		// Add where clause to query
		if ($where_clauses) $this->object->_add_where_clause($where_clauses, $operator);

		return $this->object;
	}

	/**
	 * Parses a where clause and returns an associative array
	 * representing the query
	 *
	 * E.g. parse_where_clause("post_title = %s", "Foo Bar")
	 *
	 * @global wpdb $wpdb
	 * @param string $condition
	 * @return array
	 */
	function _parse_where_clause($condition)
	{
		$column = '';
		$operator = '';
		$value = '';
		$numeric = TRUE;

		// Substitute any placeholders
		global $wpdb;
		$binds = func_get_args();
		$binds = $binds[1]; // first argument is the condition
		foreach ($binds as &$bind) {

			// A bind could be an array, used for the 'IN' operator
			// or a simple scalar value. We need to convert arrays
			// into scalar values
			if (is_object($bind))
                $bind = (array)$bind;

			if (is_array($bind) && !empty($bind)) {
				foreach ($bind as &$val) {
					if (!is_numeric($val)) {
						$val = '"'.addslashes($val).'"';
						$numeric = FALSE;
					}
				}
				$bind = implode(',', $bind);
			}
            else if (is_array($bind) && empty($bind)) {
                $bind = 'NULL';
            }
			else if(!is_numeric($bind)) {
                $numeric = FALSE;
            }
		}
		$condition = $wpdb->prepare($condition, $binds);

		// Parse the where clause
		if (preg_match("/^[^\s]+/", $condition, $match)) {
			$column = trim(array_shift($match));
			$condition = str_replace($column, '', $condition);
		}

		if (preg_match("/(NOT )?IN|(NOT )?LIKE|(NOT )?BETWEEN|[=!<>]+/i", $condition, $match)) {
			$operator = trim(array_shift($match));
			$condition = str_replace($operator, '', $condition);
			$operator = strtolower($operator);
			$value = trim($condition);
		}

		// Values will automatically be quoted, so remove them
		// If the value is part of an IN clause or BETWEEN clause and
		// has multiple values, we attempt to split the values apart into an
		// array and iterate over them individually
		if ($operator == 'in') {
			$values = preg_split("/'?\s?(,)\s?'?/i", $value);
		}
		elseif ($operator == 'between') {
			$values = preg_split("/'?\s?(AND)\s?'?/i", $value);
		}

		// If there's a single value, treat it as an array so that we
		// can still iterate
		if (empty($values)) $values = array($value);
		foreach ($values as $index => $value) {
			$value = preg_replace("/^(\()?'/", '', $value);
			$value = preg_replace("/'(\))?$/", '', $value);
			$values[$index] = $value;
		}
		if (count($values)>1) $value = $values;

		// Return the WP Query meta query parameters
		$retval = array(
			'column'	=> $column,
			'value'		=> $value,
			'compare'	=> strtoupper($operator),
			'type'		=> $numeric ? 'numeric' : 'string',
		);

		return $retval;
	}

	/**
	 * Converts a stdObject to an Entity
	 * @param stdObject $stdObject
	 * @return stdObject
	 */
	function _convert_to_entity($stdObject)
	{
		// Add name of the id_field to the entity, and convert
		// the ID to an integer
		$stdObject->id_field = $key = $this->object->get_primary_key_column();

		// Cast columns to their appropriate data type
		$this->cast_columns($stdObject);

		// Unserialize columns
		$this->unserialize_columns($stdObject);

		// Set defaults for this entity
		$this->object->set_defaults($stdObject);

		return $stdObject;
	}

	/**
	 * Converts a stdObject entity to a model
	 * @param stdObject $stdObject
	 */
	function convert_to_model($stdObject, $context=FALSE)
	{
		// Create a factory
		$retval = NULL;

		try {
			$this->object->_convert_to_entity($stdObject);
		}
		catch (Exception $ex) {
			throw new E_InvalidEntityException($ex);
		}
		$retval = $this->object->create($stdObject, $context);

		return $retval;
	}

	/**
	 * Creates a new model
	 * @param stdClass|array $properties
	 * @return C_DataMapper_Model
	 */
	function create($properties=array(), $context=FALSE)
	{
		$entity = $properties;
		$factory = $this->object->get_registry()->get_utility('I_Component_Factory');
		if (!is_object($properties)) {
			$entity = new stdClass;
			foreach ($properties as $k=>$v) $entity->$k = $v;
		}
		return $factory->create($this->object->get_model_factory_method(), $entity, $this->object, $context);
	}


	/**
	 * Determines whether an object is actually a model
	 * @param mixed $obj
	 * @return bool
	 */
	function is_model($obj)
	{
		return is_subclass_of($obj, 'C_DataMapper_Model') or get_class($obj) == 'C_DataMapper_Model';
	}

	/**
	 * Saves an entity
	 * @param stdClass|C_DataMapper_Model $entity
	 * @return bool
	 */
	function save($entity)
	{
		$retval = FALSE;
		$model  = $entity;

		// Attempt to use something else, most likely an associative array
		// TODO: Support assocative arrays. The trick is to support references
		// with dynamic calls using __call() and call_user_func_array().
		if (is_array($entity)) throw new E_InvalidEntityException();

		// We can work with what we have. But we need to ensure that we've got
		// a model
		elseif (!$this->object->is_model($entity)) {
			$model = $this->object->convert_to_model($entity);
		}

		// Validate the model
		$model->validate();

		if ($model->is_valid()) {
			$saved_entity = $model->get_entity();
			unset($saved_entity->_errors);
			$retval = $this->object->_save_entity($saved_entity);
		}

		// We always return the same type of entity that we given
		if (get_class($entity) == 'stdClass') $model->get_entity();

		return $retval;
	}


    /**
     * Gets validation errors for the entity
     * @param stdClass|C_DataMapper_Model $entity
     * @return array
     */
    function get_errors($entity)
    {
        $model = $entity;
        if (!$this->object->is_model($entity)) {
            $model = $this->object->convert_to_model($entity);
        }
        $model->validate();
        return $model->get_errors();
    }

	/**
	 * Called to set defaults for the record/model/entity.
	 * Subclasses and adapters should extend this method to provide their
	 * implementation. The implementation should make use of the
	 * _set_default_value() method
	 */
	function set_defaults()
	{

	}

	/**
	 * If a field has no value, then use the default value.
	 * @param stdClass|C_DataMapper_Model $object
	 */
	function _set_default_value($object)
	{
		$array			= NULL;
		$field			= NULL;
		$default_value	= NULL;

		// The first argument MUST be an object
		if (!is_object($object)) throw new E_InvalidEntityException();

		// This method has two signatures:
		// 1) _set_default_value($object, $field, $default_value)
		// 2) _set_default_value($object, $array_field, $field, $default_value)

		// Handle #1
		$args = func_get_args();
		if (count($args) == 4) {
			list($object, $array, $field, $default_value) = $args;
			if (!isset($object->{$array})) {
				$object->{$array} = array();
				$object->{$array}[$field] = NULL;
			}
			else {
				$arr = &$object->{$array};
				if (!isset($arr[$field])) $arr[$field] = NULL;
			}
			$array = &$object->{$array};
			$value = &$array[$field];
			if ($value === '' OR is_null($value)) $value = $default_value;
		}

		// Handle #2
		else {
			list($object, $field, $default_value) = $args;
			if (!isset($object->$field)) {
				$object->$field = NULL;
			}
			$value = $object->$field;
			if ($value === '' OR is_null($value)) $object->$field = $default_value;
		}
	}

    function scrub_result($result)
    {
        if (is_object($result))
        {
            $new_result = new stdClass();
            foreach ($result as $key => $value) {
                $new_value = $this->scrub_result($value);
                $new_result->$key = $new_value;
            }
            return $new_result;
        }
        else if (is_array($result)) {
            $new_array = array();
            foreach ($result as $key => $value) {
                $new_array[$key] = $this->scrub_result($value);
            }
            return $new_array;
        } else {
            return stripslashes($result);
        }
    }

	function define_column($name, $type, $default_value=NULL)
	{
		$this->object->_columns[$name] = array(
			'type'			=>	$type,
			'default_value'	=>	$default_value
		);
	}

	function has_defined_column($name)
	{
		$columns = $this->object->_columns;
		return isset($columns[$name]);
	}

	function cast_columns($entity)
	{
		foreach ($this->object->_columns as $key => $properties) {
			$value = isset($entity->$key) ? $entity->$key : NULL;
			$default_value = $properties['default_value'];
			if ($value && $value != $default_value) {
				$column_type = $this->object->_columns[$key]['type'];
				if (preg_match("/varchar|text/i", $column_type)) {
					if (!is_array($value) && !is_object($value))
						$entity->$key = strval($value);
				}
				else if (preg_match("/decimal|numeric|double/i", $column_type)) {
					$entity->$key = doubleval($value);
				}
				else if (preg_match("/float/i", $column_type)) {
					$entity->$key = floatval($value);
				}
				else if (preg_match("/int/i", $column_type)) {
					$entity->$key = intval($value);
				}
				else if (preg_match("/bool/i", $column_type)) {
					$entity->$key = ($value ? TRUE : FALSE);
				}
			}

			// Add property and default value
			else {
				$entity->$key = $default_value;
			}
		}
		return $entity;
	}
}

class C_DataMapper_Driver_Base extends C_Component
{
	var $_object_name;
	var $_model_factory_method = FALSE;
	var $_columns			   = array();
	var $_table_columns		   = array();
	var $_serialized_columns   = array();

	function define($object_name, $context=FALSE)
	{
		parent::define($context);
		$this->add_mixin('Mixin_DataMapper_Driver_Base');
		$this->implement('I_DataMapper_Driver');
		$this->_object_name = $object_name;

		if ($this->has_method('define_columns')) {
			$this->define_columns();
		}
	}

	function initialize()
	{
		parent::initialize();
		$this->lookup_columns();
	}

	/**
	 * Gets the object name
	 * @return string
	 */
	function get_object_name()
	{
		return $this->_object_name;
	}

	/**
	 * Gets the name of the table
	 * @global string $table_prefix
	 * @return string
	 */
	function get_table_name()
	{
		global $table_prefix;
		return $table_prefix.$this->_object_name;
	}


	/**
	 * Looks up using SQL the columns existing in the database
	 */
	function lookup_columns()
	{
		global $wpdb;
		$this->_table_columns = array();
		$sql = "SHOW COLUMNS FROM `{$this->get_table_name()}`";
		foreach ($wpdb->get_results($sql) as $row) {
			$this->_table_columns[] = $row->Field;
		}
		return $this->_table_columns;
	}

	/**
	 * Determines whether a column is present for the table
	 * @param string $column_name
	 * @return string
	 */
	function has_column($column_name)
	{
		if (empty($this->object->_table_columns)) $this->object->lookup_columns();
		return array_search($column_name, $this->object->_table_columns) !== FALSE;
	}

	/**
	 * Sets the name of the factory method used to create a model for this entity
	 * @param string $method_name
	 */
	function set_model_factory_method($method_name)
	{
		$this->_model_factory_method = $method_name;
	}


	/**
	 * Gets the name of the factory method used to create a model for this entity
	 */
	function get_model_factory_method()
	{
		return $this->_model_factory_method;
	}


	/**
	 * Gets the name of the primary key column
	 * @return string
	 */
	function get_primary_key_column()
	{
		return $this->_primary_key_column;
	}


	/**
	 * Gets the class name of the driver used
	 * @return string
	 */
	function get_driver_class_name()
	{
		return get_called_class();
	}
}
