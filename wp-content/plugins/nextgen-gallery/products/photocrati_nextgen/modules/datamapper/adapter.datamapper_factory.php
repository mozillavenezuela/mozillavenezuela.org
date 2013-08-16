<?php

class A_DataMapper_Factory extends Mixin
{
	function datamapper_model($mapper, $properties=array(), $context=FALSE)
	{
		return new C_DataMapper_Model($mapper, $properties=array(), $context);
	}

	function datamapper($object_name, $context=FALSE)
	{
		return new C_DataMapper($object_name, $context);
	}

	function custom_table_datamapper($object_name, $context=FALSE)
	{
		return new C_CustomTable_DataMapper_Driver($object_name, $context);
	}

	function custom_post_datamapper($object_name, $context=FALSE)
	{
		return new C_CustomPost_DataMapper_Driver($object_name, $context);
	}
}