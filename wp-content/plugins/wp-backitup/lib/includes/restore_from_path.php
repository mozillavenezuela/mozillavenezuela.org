<?php

/**
 * WP Backitup Restore Functions
 * 
 * @package WP Backitup
 * 
 * @author jcpeden
 * @version 1.3.0
 * @since 1.0.1
 */


//define constants
if( !defined( 'WP_DIR_PATH' ) ) define( 'WP_DIR_PATH', dirname ( dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ) ) );

if( !defined( 'WPBACKITUP_DIR_PATH' ) ) define( 'WPBACKITUP_DIR_PATH', dirname( dirname( dirname( __FILE__ ) ) ) );

if( !defined( 'WPBACKITUP_DIRNAME' ) ) define( 'WPBACKITUP_DIRNAME', basename(WPBACKITUP_DIR_PATH) );

if( !defined( 'WP_CONTENT_PATH' ) ) define( 'WP_CONTENT_PATH', dirname( dirname( WPBACKITUP_DIR_PATH ) ) ) ;


//create log file
$log = WPBACKITUP_DIR_PATH .'/backups/status.log';
$fh = fopen($log, 'w') or die( "Can't open file" );
echo '<ul>';

// 15 minutes per image should be PLENTY
	@set_time_limit(900);

//define create_dir function
if(!function_exists('create_dir')) {
	function create_dir($dir) {
		if( !is_dir($dir) ) {
			@mkdir($dir, 0755);
		}
		return true;
	}
}

//Define recusive_copy function
if(!function_exists('recursive_copy')) {
	function recursive_copy($dir, $target_path, $ignore = array( 'cgi-bin','..','._' ) ) {
		if( is_dir($dir) ) { //If the directory exists
			if ($dh = opendir($dir) ) {
				while(($file = readdir($dh)) !== false) { //While there are files in the directory
					if ( !in_array($file, $ignore) && substr($file, 0, 1) != '.') { //Check the file is not in the ignore array
						if (!is_dir( $dir.$file ) ) {
								//Copy files to destination directory
								//echo 'Copying ' .$dir .$file . ' to ' .$target_path .$file .'<br />';
								$fsrc = fopen($dir .$file,'r');
								$fdest = fopen($target_path .$file,'w+');
								$len = stream_copy_to_stream($fsrc,$fdest);
								fclose($fsrc);
								fclose($fdest);
						} else { //If $file is a directory
							$destdir = $target_path .$file; //Modify the destination dir
							if(!is_dir($destdir)) { //Create the destdir if it doesn't exist
								@mkdir($destdir, 0755);
							} 	
							recursive_copy($dir .$file .'/', $target_path .$file .'/', $ignore);
						}
					}
				}
				closedir($dh);
			}
		}
	return true;
	}	
}

//Define recursive_delete function
if(!function_exists('recursive_delete')){
	function recursive_delete($dir, $ignore = array('cgi-bin','.','..','._') ){		  
		if( is_dir($dir) ){
			if($dh = opendir($dir)) {
				while( ($file = readdir($dh)) !== false ) {
					if (!in_array($file, $ignore) && substr($file, 0, 1) != '.') { //Check the file is not in the ignore array
						if(!is_dir($dir .'/' .$file)) {
							//echo 'Deleting ' .$dir .'/' .$file '<br />';
							unlink($dir .'/' .$file);
						} else {
							recursive_delete($dir .'/' .$file, $ignore);
						}
					}
				}
			}
			@rmdir($dir);	
			closedir($dh);
		}
	return true;
	}
}

//define db_import function
if(!function_exists('db_import')) {
	function db_import($restoration_dir_path, $import_siteurl, $current_siteurl, $table_prefix, $import_table_prefix, $dbc) {
		global $wpdb;
		$sql_files = glob($restoration_dir_path . "*.sql");
		foreach($sql_files as $sql_file) {
			$templine = ''; // Temporary variable, used to store current query
			$lines = file($sql_file); // Read in entire file
			foreach ($lines as $line) { // Loop through each line
				if (substr($line, 0, 2) == '--' || $line == '') continue; // Skip it if it's a comment
				$templine .= $line; // Add this line to the current segment
				if (substr(trim($line), -1, 1) == ';') { // If it has a semicolon at the end, it's the end of the query
					//replace imported site url with current site url
					if( strstr( trim($templine), trim($import_siteurl) ) == TRUE ) //If import site url is found
					$templine = str_replace( trim($import_siteurl), trim($current_siteurl), $templine ); // Replace import site url with current site url
					//if the table prefixes are different, replace the imported site prefixes with the current prefixes
					if ($table_prefix != $import_table_prefix) {
						if( strstr( trim($templine), trim($import_table_prefix) ) == TRUE ) //If import table prefix is found
						$templine = str_replace( trim($import_table_prefix), trim($table_prefix), $templine ); // Replace import site table prefix with current site table prefix
					}
					// Perform the query
					if( mysqli_query($dbc, $templine) === FALSE) 
						print('Error performing query \'<strong>' . $templine . '\': ' . mysql_error() . '<br /><br />');
					$templine = ''; // Reset temp variable to empty
				}
			}
		}
	return true;
	}
}

//Delete any zips in the upload directory first
foreach (glob(WPBACKITUP_DIR_PATH .'/backups/' .'*.zip') as $file) {
	unlink($file);
}
 
//Move the uploaded zip to the restoration directory
$restore_file_name = $_GET['name'];

$orig_file_name = WP_DIR_PATH . "/wp-content/uploads/" . $_GET['name'];
if( $restore_file_name == '') {
	echo '<li class="error">No file selected<li></ul>' ;
	fclose($fh);
	die();
} else {
	echo '<li>Uploading restoration file...' ;
}

$restore_path = WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name; 
if(copy($orig_file_name, $restore_path)) {
	echo "Done!</li>";
} else {
	echo '</li><li class="error">Your file could not be uploaded</li></ul>';
	fclose($fh);
	die();
}

//Unzip the uploaded restore file	 
echo "<li>Unzipping...";
//include recurse_zip.php
include_once 'recurse_zip.php';
//unzip the upload
$zip = new ZipArchive;
$res = $zip->open(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
if ($res === TRUE) {
	$zip->extractTo(WPBACKITUP_DIR_PATH .'/backups/');
	$zip->close();
	echo 'Done!</li>';		
} else {
	echo '</li><li class="error">Your restoration file could not be unzipped.</li></ul>';
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}

//Identify the restoration directory
echo '<li>Validating zip...' ;
if ( count( glob( WPBACKITUP_DIR_PATH .'/backups/*', GLOB_ONLYDIR ) ) == 1 ) {
	foreach( glob(WPBACKITUP_DIR_PATH .'/backups/*', GLOB_ONLYDIR ) as $dir) {
		$restoration_dir_path = $dir .'/';
	}
}

//Validate the restoration
if(glob($restoration_dir_path .'backupsiteinfo.txt') ){
	echo 'Done!</li>';
} else {
	echo '</li><li class="error">Your zip file appears to be invalid. Please ensure you chose the correct zip file.</li></ul>';
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}

//Restore wp-content directories
echo "<li>Restoring wp-content directory..." ;
if(!recursive_delete(WP_CONTENT_PATH, array( 'cgi-bin','.','..','._', WPBACKITUP_DIRNAME ))) {
	echo '</li><li class="error">Unable to remove existing wp-content directory for import. Please check your CHMOD settings in /wp-content/.</li></ul>';
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}
if(!create_dir(WP_CONTENT_PATH)) {
	echo '</li><li class="error">Unable to create new wp-content directory for import. Please check your CHMOD settings in /wp-content/.</li></ul>';
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}
if(recursive_copy($restoration_dir_path, WP_CONTENT_PATH .'/', array( 'cgi-bin', '.', '..','._', $restore_file_name, 'status.log', 'db-backup.sql', 'backupsiteinfo.txt')) ) {
	echo 'Done!</li>';
} else {
	echo '</li><li class="error">Unable to import wp-content. Please try again.</li></ul>';
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}

//if there is a database dump to restore
if( glob($restoration_dir_path . "*.sql") ) {
	//collect connection information from form
	echo "<li>Restoring database..." ;
	include_once WP_DIR_PATH .'/wp-config.php';
	//Add user to DB in v1.0.5
	$user_id = $_GET['userid'];
	//Connect to DB
	$dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	if ( !$dbc ) {
		echo '</li><li class="error">Unable to connect to your current database: " .mysqli_connect_error() ."</li></ul>';
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	//get siteurl
	$q1 = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name ='siteurl';";
	if ($result = mysqli_query($dbc, $q1)) {
		while ($row = mysqli_fetch_row($result)) {
			$siteurl = $row[0];
		}
		mysqli_free_result($result);
	} else {
		echo '</li><li class="error">Unable to get current site URL from database. Please try again.</li></ul>';
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	//get homeurl
	$q2 = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name ='home';";
	if ($result = mysqli_query($dbc, $q2)) {
		while ($row = mysqli_fetch_row($result)) {
			$homeurl = $row[0];
		}
		mysqli_free_result($result);
	} else {
		echo '</li><li class="error">Unable to get current home URL from database. Please try again.</li></ul>';
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	//get user login
	$q3 = "SELECT user_login FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";
	if ($result = mysqli_query($dbc, $q3)) {
		while ($row = mysqli_fetch_row($result)) {
			$user_login = $row[0];
		}
		mysqli_free_result($result);
	} else {
		echo '</li><li class="error">Unable to get current user ID from database. Please try again.</li></ul>';
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	//get user pass
	$q4 = "SELECT user_pass FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";
	if ($result = mysqli_query($dbc, $q4)) {
		while ($row = mysqli_fetch_row($result)) {
			$user_pass = $row[0];
		}
		mysqli_free_result($result);
	} else {
		echo '</li><li class="error">Unable to get current user password from database. Please try again.</li></ul>';
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	//get user email
	$q5 = "SELECT user_email FROM ". $table_prefix ."users WHERE ID=" .$user_id ."";
	if ($result = mysqli_query($dbc, $q5)) {
		while ($row = mysqli_fetch_row($result)) {
			$user_email = $row[0];
		}
		mysqli_free_result($result);
	} else {
		echo '</li><li class="error">Unable to get current user email from database. Please try again.</li></ul>';
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	//Collect previous backup site url start
	$import_siteinfo_lines = file($restoration_dir_path .'backupsiteinfo.txt');
	$import_siteurl = trim($import_siteinfo_lines[0]);
	$current_siteurl = trim($siteurl ,'/');
	$import_table_prefix = $import_siteinfo_lines[1];
	//import the database
	if(!db_import($restoration_dir_path, $import_siteurl, $current_siteurl, $table_prefix, $import_table_prefix, $dbc)) {
		echo '</li><li class="error">Unable to get import your database. This may require importing the file manually.</li></ul>';
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	//update the database
	$q6 = "UPDATE ". $table_prefix ."options SET option_value='" .$current_siteurl ."' WHERE option_name='siteurl'";
	$q7 = "UPDATE ". $table_prefix ."options SET option_value='" .$homeurl ."' WHERE option_name='home'";
	$q8 = "UPDATE ". $table_prefix ."users SET user_login='" .$user_login ."', user_pass='" .$user_pass ."', user_email='" .$user_email ."' WHERE ID='" .$user_id ."'";
	if(!mysqli_query($dbc, $q6) ) {
		echo '</li><li class="error">Unable to update your current site URL value. This may require importing the file manually.</li></ul>';
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	if(!mysqli_query($dbc, $q7) ) {
		echo '</li><li class="error">Unable to update your current home URL value. This may require importing the file manually.</li></ul>';
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}	
	if(!mysqli_query($dbc, $q8) ) {
		echo '</li><li class="error">Unable to update your user information. This may require importing the file manually.</li></ul>';
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	echo 'Done!</li>';	
} else {
	echo '<li class="error">Warning: Database not detected in import file.</li>';
}

//Disconnect
mysqli_close($dbc); 

//Delete the restoration directory
recursive_delete($restoration_dir_path);

//Delete zip
unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);

//close log file
echo '<li>Restoration complete. Please refresh the page.</li>';
echo '</ul>';
fclose($fh);

//End backup function
die();