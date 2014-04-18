<?php

class Mixin_CustomPost_DataMapper_Driver extends Mixin
{

	/**
	 * Returns a list of querable table columns for posts
	 * @return array
	 */
	function _get_querable_table_columns()
	{
		return array('name', 'author', 'date', 'title', 'modified', 'menu_order', 'parent', 'ID', 'rand', 'comment_count');
	}

	/**
	 * Used to select which fields should be returned. NOT currently used by
	 * this implementation of the datamapper driver
	 * @param type $fields
	 * @return C_DataMapper_Driver_Base
	 */
	function select($fields='*')
	{
		$this->object->_query_args = array(
			'post_type'		=> $this->object->get_object_name(),
			'paged'			=> FALSE,
			'fields'		=> $fields,
			'post_status'	=> 'any',
			'datamapper'	=>	TRUE,
			'posts_per_page'=> -1,
			'is_select'		=>	TRUE,
			'is_delete'		=>	FALSE
		);

		return $this->object;
	}


	/**
	 * Specifies an order clause
	 * @param type $order_by
	 * @param type $direction
	 * @return C_DataMapper_Driver_Base
	 */
	function order_by($order_by, $direction='ASC')
	{
		// Make an exception for the rand() method
		$order_by = preg_replace("/rand\(\s*\)/", 'rand', $order_by);

		if (in_array($order_by, $this->object->_get_querable_table_columns())) {
			$this->object->_query_args['orderby'] = $order_by;
		}
		else { // ordering by a meta value
			$this->object->_query_args['orderby'] = 'meta_value';
			$this->object->_query_args['meta_key'] = $order_by;
		}
		$this->object->_query_args['order'] = $direction;

		return $this->object;
	}

	/**
	 * Specifies a limit and optional offset
	 * @param integer $max
	 * @param integer $offset
	 * @return C_DataMapper_Driver_Base
	 */
	function limit($max, $offset=FALSE)
	{
		if ($max) {
			$this->object->_query_args['paged'] = TRUE;
			$this->object->_query_args['offset'] = $offset;
			$this->object->_query_args['posts_per_page'] = $max;
		}

		return $this->object;
	}


    /**
     * Specifies a list of columns to group by
     * @param array|string $columns
     */
    function group_by($columns=array())
    {
        if (!isset($this->object->_query_args['group_by_columns']))
            $this->object->_query_args['group_by_columns'] = $columns;
        else {
            $this->object->_query_args['group_by_columns'] = array_merge(
              $this->object->_query_args['group_by_columns'],
              $columns
            );
        }

        return $this->object;
    }

	/**
	 * Adds a WP_Query where clause
	 * @param array $where_clauses
	 * @param string $join
	 */
	function _add_where_clause($where_clauses, $join)
	{
		foreach ($where_clauses as $clause) {
			// $clause => array(
			// 'column' => 'ID',
			// 'value'  =>	1210,
			// 'compare' => '='
			// )

			// Determine where what the where clause is comparing
			switch($clause['column']) {
				case 'author':
			    case 'author_id':
					$this->object->_query_args['author'] = $clause['value'];
					break;
				case 'author_name':
					$this->object->_query_args['author_name'] = $clause['value'];
					break;
				case 'cat':
				case 'cat_id':
				case 'category_id':
					switch($clause['compare']) {
						case '=':
						case 'BETWEEN';
						case 'IN';
							if (!isset($this->object->_query_args['category__in'])) {
								$this->object->_query_args['category__in'] = array();
							}
							$this->object->_query_args['category__in'][] = $clause['value'];
							break;
						case '!=':
						case 'NOT BETWEEN';
						case 'NOT IN';
							if (!isset($this->object->_query_args['category__not_in'])) {
								$this->object->_query_args['category__not_in'] = array();
							}
							$this->object->_query_args['category__not_in'][] = $clause['value'];
							break;
					}
					break;
				case 'category_name':
					$this->object->_query_args['category_name'] = $clause['value'];
					break;
				case 'post_id':
				case $this->object->get_primary_key_column():
					switch ($clause['compare']) {
						case '=':
						case 'IN';
						case 'BETWEEN';
							if (!isset($this->object->_query_args['post__in'])) {
								$this->object->_query_args['post__in'] = array();
							}
							$this->object->_query_args['post__in'][] = $clause['value'];
							break;
						default:
							if (!isset($this->object->_query_args['post__not_in'])) {
								$this->object->_query_args['post__not_in'] = array();
							}
							$this->object->_query_args['post__not_in'][] = $clause['value'];
							break;
					}
					break;
				case 'pagename':
				case 'postname':
				case 'page_name':
				case 'post_name':
					if ($clause['compare'] == 'LIKE')
						$this->object->_query_args['page_name__like'] = $clause['value'];
					elseif ($clause['compare'] == '=')
						$this->object->_query_args['pagename'] = $clause['value'];
					elseif ($clause['compare'] == 'IN')
						$this->object->_query_args['page_name__in'] = $clause['value'];
					break;
				case 'post_title':
					// Post title uses custom WHERE clause
					if ($clause['compare'] == 'LIKE')
						$this->object->_query_args['post_title__like'] = $clause['value'];
					else
						$this->object->_query_args['post_title'] = $clause['value'];
					break;
				default:
					// Must be metadata
					$clause['key'] = $clause['column'];
					unset($clause['column']);

					// Convert values to array, when required
					if (in_array($clause['compare'], array('IN', 'BETWEEN'))) {
						$clause['value'] = explode(',', $clause['value']);
						foreach ($clause['value'] as &$val) {
							if (!is_numeric($val)) {

								// In the _parse_where_clause() method, we
								// quote the strings and add slashes
								$val = stripslashes($val);
								$val = substr($val, 1, strlen($val)-2);
							}
						}
					}

					if (!isset($this->object->_query_args['meta_query'])) {
						$this->object->_query_args['meta_query'] = array();
					}
					$this->object->_query_args['meta_query'][] = $clause;
					break;
			}
		}

		// If any where clauses have been added, specify how the conditions
		// will be conbined/joined
		if (isset($this->object->_query_args['meta_query'])) {
			$this->object->_query_args['meta_query']['relation'] = $join;
		}

	}


	/**
	 * Destroys/deletes an entity from the database
	 * @param stdObject|C_DataMapper_Model $entity
	 * @return type
	 */
	function destroy($entity, $skip_trash=TRUE)
	{
		$retval = FALSE;

		$key = $this->object->get_primary_key_column();

		// Find the id of the entity
		if (is_object($entity) && isset($entity->$key)) {
			$id = (int)$entity->$key;
		}
		else {
			$id = (int)$entity;
		}

		// If we have an ID, then delete the post
		if (is_integer($id)) {

			// TODO: We assume that we can skip the trash. Is that correct?
			// FYI, Deletes postmeta as wells
			if (is_object(wp_delete_post($id, TRUE))) $retval = TRUE;
		}

		return $retval;
	}

	/**
	 * Converts a post to an entity
	 * @param \stdClass $post
	 * @param boolean $model
	 * @return \stdClass
	 */
	function convert_post_to_entity($post, $model=FALSE)
	{
		$entity = new stdClass();

		// Unserialize the post content field
		if (is_string($post->post_content)) {
			if (($post_content = $this->object->unserialize($post->post_content))) {
				foreach ($post_content as $key => $value) {
					$post->$key = $value;
				}
			}

		}
		unset($post->post_content);

		// Copy all fields to the entity
		foreach ($post as $key => $value) {
			$entity->$key = $value;
		}
        $this->object->_convert_to_entity($entity);
		return $model? $this->object->convert_to_model($entity) : $entity;
	}


	/**
	 * Converts an entity to a post
	 * @param type $entity
	 * @return type
	 */
	function _convert_entity_to_post($entity)
	{
		// Was a model passed instead of an entity?
		$post = $entity;
		if (!($entity instanceof stdClass)) $post = $entity->get_entity();

		// Create the post content
		$post_content = clone $post;
		foreach ($this->object->_table_columns as $column) unset($post_content->$column);
		unset($post->id_field);
		unset($post->post_content_filtered);
		unset($post->post_content);
		$post->post_content = $this->object->serialize($post_content);
		$post->post_content_filtered = $post->post_content;
		$post->post_type = $this->object->get_object_name();

		// Sometimes an entity can contain a data stored in an array or object
		// Those will be removed from the post, and serialized in the
		// post_content field
		foreach ($post as $key => $value) {
			if (in_array(strtolower(gettype($value)), array('object','array')))
				unset($post->$key);
		}

		// A post required a title
		if (!property_exists($post, 'post_title')) {
			$post->post_title = $this->object->get_post_title($post);
		}

		// A post also requires an excerpt
		if (!property_exists($post, 'post_excerpt')) {
			$post->post_excerpt = $this->object->get_post_excerpt($post);
		}

		return $post;
	}

	/**
	 * Returns the WordPress database class
	 * @global wpdb $wpdb
	 * @return wpdb
	 */
	function _wpdb()
	{
		global $wpdb;
		return $wpdb;
	}


	/**
	 * Flush and update all postmeta for a particular post
	 * @param int $post_id
	 */
	function _flush_and_update_postmeta($post_id, $entity, $omit=array())
	{
		// We need to insert post meta data for each property
		// Unfortunately, that means flushing all existing postmeta
		// and then inserting new values. Depending on the number of
		// properties, this could be slow. So, we directly access the database
		/* @var $wpdb wpdb */
		global $wpdb;
		if (!is_array($omit)) $omit = array($omit);

		// By default, we omit creating meta values for columns in the posts table
		$omit = array_merge($omit, $this->object->_table_columns);

		// Delete the existing meta values
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE post_id = %s", $post_id));

		// Create query for new meta values
		$sql_parts = array();
		foreach($entity as $key => $value) {
			if (in_array($key, $omit)) continue;
			if (is_array($value) or is_object($value)) {
				$value = $this->object->serialize($value);
			}
			$sql_parts[] = $wpdb->prepare("(%s, %s, %s)", $post_id, $key, $value);
		}
		$wpdb->query("INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES ".implode(',', $sql_parts));
	}


	/**
	 * Saves an entity to the database
	 * @param stdObject $entity
	 */
	function _save_entity($entity)
	{
        $post = $this->object->_convert_entity_to_post($entity);
		$primary_key = $this->object->get_primary_key_column();

        // TODO: unsilence this. Wordpress 3.9-beta2 is generating an error that should be corrected before its
        // final release.
		if (($post_id = @wp_insert_post($post))) {

			$new_entity = $this->object->find($post_id, TRUE);
			foreach ($new_entity->get_entity() as $key => $value) $entity->$key = $value;

			// Save properties as post meta
			$this->object->_flush_and_update_postmeta(
				$post_id,
				$entity instanceof stdClass ? $entity : $entity->get_entity()
			);

			$entity->$primary_key = $post_id;
		}
		$entity->id_field = $primary_key;

		return $post_id;
	}


	/**
	 * Determines whether the current statement is SELECT
	 * @return boolean
	 */
	function is_select_statement()
	{
		return isset($this->object->_query_args['is_select']) && $this->object->_query_args['is_select'];
	}


	/**
	 * Determines whether the current statement is DELETE
	 * @return type
	 */
	function is_delete_statement()
	{
		return isset($this->object->_query_args['is_delete']) && $this->object->_query_args['is_delete'];
	}


	/**
	 * Starts a new DELETE statement
	 */
	function delete()
	{
		$this->object->select();
		$this->object->_query_args['is_select'] = FALSE;
		$this->object->_query_args['is_delete'] = TRUE;
		return $this->object;
	}


	/**
	 * Runs the query
	 * @param  string $sql optionally run the specified query
	 * @return array
	 */
	function run_query($sql=FALSE, $model=FALSE, $convert_to_entities=TRUE)
	{
		$retval = array();

		if ($sql)
        {
			$this->object->_query_args['cache_results'] = FALSE;
			$this->object->_query_args['custom_sql'] = $sql;
		}

		// Execute the query
		$query = new WP_Query();
		if (isset($this->object->debug)) $this->object->_query_args['debug'] = TRUE;
		$query->query_vars = $this->object->_query_args;
		add_action('pre_get_posts', array(&$this, 'set_query_args'), PHP_INT_MAX-1, 1);
        if ($convert_to_entities) foreach ($query->get_posts() as $row) {
			$retval[] = $this->object->convert_post_to_entity($row, $model);
		}
        else $retval = $query->get_posts();
		remove_action('pre_get_posts', array(&$this, 'set_query_args'), PHP_INT_MAX-1, 1);

		return $retval;
	}

	/**
	 * Ensure that the query args are set. We need to do this in case a third-party
	 * plugin overrides our query
	 * @param $query
	 */
	function set_query_args($query)
	{
		if ($query->get('datamapper')) $query->query_vars = $this->object->_query_args;
	}

		/**
	 * Fetches the last row
	 * @param array $conditions
	 * @return C_DataMapper_Entity
	 */
	function find_last($conditions=array(), $model=FALSE)
	{
		$retval = NULL;

		// Get row number for the last row
		$table_name = $this->object->_clean_column($this->object->get_table_name());
		$object_name = $this->object->_clean_column($this->object->get_object_name());
		$sql = $this->_wpdb()->prepare("SELECT COUNT(*) FROM {$table_name} WHERE post_type = %s", $object_name);
		$count = $this->_wpdb()->get_var($sql);
		$offset = $count-1;
		$results = $this->select()->where_and($conditions)->limit(1, $offset)->run_query();
		if ($results) {
			$retval = $model? $this->object->convert_to_model($results[0]) : $results[0];
		}

		return $retval;
	}



	/**
	 * Returns the number of total records/entities that exist
	 * @return int
	 */
	function count()
	{
        $this->object->select($this->object->get_primary_key_column());
		$retval = $this->object->run_query(FALSE, FALSE, FALSE);

		return count($retval);
	}


	/**
	 * Returns the title of the post. Used when post_title is not set
	 * @param stdClass $entity
	 * @return string
	 */
	function get_post_title($entity)
	{
		return "Untitled {$this->object->get_object_name()}";
	}

	/**
	 * Returns the excerpt of the post. Used when post_excerpt is not set
	 * @param stdClass $entity
	 * @return string
	 */
	function get_post_excerpt($entity)
	{
		return '';
	}
}

class C_CustomPost_DataMapper_Driver extends C_DataMapper_Driver_Base
{
	var $_query_args = array();
	var $_primary_key_column = 'ID';

	function define($object_name, $context=FALSE)
	{
		if (strlen($object_name) > 20) throw new Exception("The custom post name can be no longer than 20 characters long");

		parent::define($object_name, $context);
		$this->add_mixin('Mixin_CustomPost_DataMapper_Driver');
		$this->implement('I_CustomPost_DataMapper');
	}


	/**
	 * Gets the name of the table
	 * @global string $table_prefix
	 * @return string
	 */
	function get_table_name()
	{
		global $table_prefix;
		return $table_prefix.'posts';
	}
}
