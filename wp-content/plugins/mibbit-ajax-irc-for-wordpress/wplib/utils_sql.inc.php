<?php
/**
 * Wordpress SQL Utility Library
 * 
 * A group of functions to make it easier to work with mysql database SQL statements.
 * 
 * This code is very much in alpha phase, and should not be distributed with plugins 
 * other than by Dan Harrison. 
 * 
 * @author Dan Harrison (http://www.danharrison.co.uk)
 *
 * Version History
 * 
 * V0.01 - Initial version released.
 *
 */

/**
 * Returns the correct SQL to INSERT the specified values as columnname => data into the specified
 * table, escaping all of the data values.
 * 
 * @param $tablename The name of the table to insert into.
 * @param $dataarray The list of values as columnname => data.
 * @return String Valid SQL to allow the specified values to be safely INSERTed into the database.
 */
if (!function_exists('arrayToSQLInsert')) { function arrayToSQLInsert($tablename, $dataarray)
{
	global $wpdb; 
	
	// Handle dodgy data
	if (!$tablename || !$dataarray || count($dataarray) == 0) {
		return false;	
	}
	
	$SQL = "INSERT INTO $tablename (";
	
	// Insert Column Names
	$columnnames = array_keys($dataarray);
	foreach ($columnnames AS $column) {
		$SQL .= $column . ", ";
	}
	
	// Remove last comma to maintain valid SQL
	if (substr($SQL, -2) == ', ') {
		$SQL = substr($SQL, 0, strlen($SQL)-2);
	}
	
	$SQL .= ") VALUES (";
	
	// Now add values, escaping them all
	foreach ($dataarray AS $columnname => $datavalue) {
		$SQL .= "'" . $wpdb->escape($datavalue) . "', ";
	}
	
	// Remove last comma to maintain valid SQL
	if (substr($SQL, -2) == ', ') {
		$SQL = substr($SQL, 0, strlen($SQL)-2);
	}	
	
	return $SQL . ")";
}}

/**
 * Returns the correctly formed SQL to UPDATE the specified values in the database 
 * using the <code>$wherecolumn</code> field to determine which field is used as part 
 * of the WHERE clause of the SQL statement. The fields and data are specified in an 
 * array mapping columnname => data.
 * 
 * @param $tablename The name of the table to UPDATE.
 * @param $dataarray The list of values as columnname => data.
 * @param $wherecolumn The column to use in the WHERE clause.  
 * @return String Valid SQL to allow the specified values to be safely UPDATEed in the database.
 */
if (!function_exists('arrayToSQLUpdate')) { function arrayToSQLUpdate($tablename, $dataarray, $wherecolumn)
{
	global $wpdb; 
	
	// Handle dodgy data
	if (!$tablename || !$dataarray || !$wherecolumn || count($dataarray) == 0) {
		return false;	
	}
	
	$SQL = "UPDATE $tablename SET ";
		
	// Now add values, escaping them all
	foreach ($dataarray AS $columnname => $datavalue)
	{
		// Do all fields except column we're using on the WHERE part
		if ($columnname != $wherecolumn) {
			$SQL .= "$columnname = '" . $wpdb->escape($datavalue) . "', ";
		}
	}
	
	// Remove last comma to maintain valid SQL
	if (substr($SQL, -2) == ', ') {
		$SQL = substr($SQL, 0, strlen($SQL)-2);
	}	
	
	// Now add the WHERE clause
	$SQL .= " WHERE $wherecolumn = '" . $wpdb->escape($dataarray[$wherecolumn]) . "'";
	
	return $SQL;
}}

	
?>