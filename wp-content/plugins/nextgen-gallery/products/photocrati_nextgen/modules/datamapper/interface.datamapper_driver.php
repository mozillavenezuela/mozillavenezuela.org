<?php

interface I_DataMapper_Driver
{
	function select($fields='*');
	function order_by($order_by, $direction);
	function limit($offset, $limit);
	function where_and($conditions=array());
	function where($conditions=array());
	function where_or($conditions=array());
    function group_by($columns=array());
	function find($id=NULL);
	function find_first();
	function find_last();
	function find_all();
	function run_query();
	function get_table_name();
	function get_object_name();
	function _save_entity($entity);
	function get_primary_key_column();
	function get_model_factory_method();
	function set_model_factory_method($method_name);
	function count();
	function convert_to_model($stdObject, $context=FALSE);
	function get_driver_class_name();
	function is_select_statement();
	function is_delete_statement();
	function delete();
}