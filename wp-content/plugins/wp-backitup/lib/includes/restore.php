<?php

/**
 * WP Backitup Restore Functions
 * 
 * @package WP Backitup Pro
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

if(!function_exists('create_dir')) {
	function create_dir($dir) {
		if( !is_dir($dir) ) {
			@mkdir($dir, 0755);
		}
		return true;
	}
}

if(!function_exists('redo_to_checkpoint')) {
	function redo_to_checkpoint($checkpoint) {
		
            if($checkpoint == "db")
            {
                if( glob($restoration_dir_path . "*.cur") ) {
                    //collect connection information from form
                    fwrite($fh, '<status code="database">In Progress</status>');
                    include_once WP_DIR_PATH .'/wp-config.php';
                    //Add user to DB in v1.0.5
                    $user_id = $_POST['user_id'];
                    //Connect to DB
                    $output = db_import($restoration_dir_path, $import_siteurl, $current_siteurl, $table_prefix, $import_table_prefix, $dbc); 
                }

            }
            
	}
}

if(!function_exists('db_backup')) {
	function db_backup($path) { 
            $handle = fopen($path .'db-backup.cur', 'w+');
            $path_sql = $path .'db-backup.cur';
            $db_name = DB_NAME; 
            $db_user  = DB_USER;
            $db_pass = DB_PASSWORD; 
            $db_host = DB_HOST;
            $output = shell_exec("mysqldump --user $db_user --password=$db_pass $db_name > '$path_sql'");
            fwrite($handle,$output);
            fclose($handle);
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
		//13-4-13: John C Peden [mail@johncpeden.com] This was incomplete, I've updated to make it work
		foreach(glob($restoration_dir_path . "*.sql") as $sql_file) {
            $db_name = DB_NAME; 
            $db_user  = DB_USER;
            $db_pass = DB_PASSWORD; 
            $db_host = DB_HOST;
            $command = "mysql --user='$db_user' --password='$db_pass' --host='$db_host' $db_name < '$sql_file'";
            $output = shell_exec(($command));
		}
	return true;
	}
}

//create log file
$log = WPBACKITUP_DIR_PATH .'/logs/status.log';
unlink($log);
$fh = fopen($log, 'w') or die( "Can't write to log file" );

// 15 minutes per image should be PLENTY
@set_time_limit(900);

//Delete the existing backup directory
recursive_delete( WPBACKITUP_DIR_PATH .'/backups/' );

//Re-create and empty backup dir
if(!create_dir( WPBACKITUP_DIR_PATH .'/backups/' )) {
	fwrite($fh, '<status code="errorMessage">Error: Unable to create new directory for import. Please check your CHMOD settings in' .WPBACKITUP_DIR_PATH.'.</status>');
	fclose($fh);
	die();
}
 
//Move the uploaded zip to the restoration directory
$restore_file_name = basename( $_FILES['wpbackitup-zip']['name']);
if( $restore_file_name == '') {
	fwrite($fh, '<status code="errorMessage">Error: No file selected</status>');
	fclose($fh);
	die();
} else {
	fwrite($fh, '<status code="upload">Done</status>');
}

//define create_dir function

fwrite($fh, '<status code="copyfiles">In Progress</status>');

$restore_path = WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name; 
if(move_uploaded_file($_FILES['wpbackitup-zip']['tmp_name'], $restore_path)) {
	// fwrite($fh, "Done!</li>");
} else {
	// fwrite($fh, '</li><li class="error">Error: Your file could not be uploaded</li></ul>');
	fclose($fh);
	die();
}

//Unzip the uploaded restore file	 
fwrite($fh, '<status code="unzipping">In Progress</status>');

//include recurse_zip.php
include_once 'recurse_zip.php';
//unzip the upload
$zip = new ZipArchive;
$res = $zip->open(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
if ($res === TRUE) {
	$zip->extractTo(WPBACKITUP_DIR_PATH .'/backups/');
	$zip->close();
	fwrite($fh, '<status code="unzipping">Done</status>');
} else {
	fwrite($fh, '<status code="errorMessage">Error: Your restoration file could not be unzipped.</status>');
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}

//Identify the restoration directory
fwrite($fh, '<status code="validation">In Progress</status>');
if ( count( glob( WPBACKITUP_DIR_PATH .'/backups/*', GLOB_ONLYDIR ) ) == 1 ) {
	foreach( glob(WPBACKITUP_DIR_PATH .'/backups/*', GLOB_ONLYDIR ) as $dir) {
		$restoration_dir_path = $dir .'/';
	}
}

//Validate the restoration
if(glob($restoration_dir_path .'backupsiteinfo.txt') ){
	fwrite($fh, '<status code="validation">Done</status>');
} else {
	fwrite($fh, '<status code="errorMessage">Error: Your zip file appears to be invalid. Please ensure you chose the correct zip file.</status>');
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}
// Backup the current database
// 
db_backup($restoration_dir_path);

//if there is a database dump to restore
if( glob($restoration_dir_path . "*.sql") ) {
	//collect connection information from form
	fwrite($fh, '<status code="database">In Progress</status>');
	include_once WP_DIR_PATH .'/wp-config.php';
	//Add user to DB in v1.0.5
	$user_id = $_POST['user_id'];
	//Connect to DB
    //$output = db_import($restoration_dir_path, $import_siteurl, $current_siteurl, $table_prefix, $import_table_prefix, $dbc);         
    //13-4-13: John C Peden [mail@johncpeden.com] This seems to be erroneous, I've commented out.
	$dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	if ( !$dbc ) {
		fwrite($fh, '<status code="errorMessage">Error: Unable to connect to your current database: '. mysqli_connect_error() , '</status>');
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
		fwrite($fh, '<status code="errorMessage">Error: Unable to get current site URL from database. Please try again.</status>');
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
		fwrite($fh, '<status code="errorMessage">Error: Unable to get current home URL from database. Please try again.</status>');
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
		fwrite($fh, '<status code="errorMessage">Error: Unable to get current user ID from database. Please try again.</status>');
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
		fwrite($fh, '<status code="errorMessage">Error: Unable to get current user password from database. Please try again.</status>');
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
		fwrite($fh, '<status code="errorMessage">Error: Unable to get current user email from database. Please try again.</status>');
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
		fwrite($fh, '<status code="errorMessage">Error: Unable to get import your database. This may require importing the file manually.</status>');
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
		fwrite($fh, '<status code="errorMessage">Error: Unable to update your current site URL value. This may require importing the file manually.</status>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	if(!mysqli_query($dbc, $q7) ) {
		fwrite($fh, '<status code="errorMessage">Error: Unable to update your current home URL value. This may require importing the file manually.</status>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}	
	if(!mysqli_query($dbc, $q8) ) {
		fwrite($fh, '<status code="errorMessage">Error: Unable to update your user information. This may require importing the file manually.</status>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	fwrite($fh, '<status code="database">Done</status>');
} else {
	fwrite($fh, '<status code="errorMessage">Error: Warning: Database not detected in import file.</status>');
}

//Disconnect
mysqli_close($dbc); 
//Restore wp-content directories
fwrite($fh, '<status code="wpcontent">In Progress</status>');
if(!recursive_delete(WP_CONTENT_PATH, array( 'cgi-bin','.','..','._', WPBACKITUP_DIRNAME ))) {
	fwrite($fh, '<status code="errorMessage">Error: Unable to remove existing wp-content directory for import. Please check your CHMOD settings in /wp-content/.</status>');
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}
if(!create_dir(WP_CONTENT_PATH)) {
	fwrite($fh, '<status code="errorMessage">Error: Unable to create new wp-content directory for import. Please check your CHMOD settings in /wp-content/.</status>');
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}
if(recursive_copy($restoration_dir_path, WP_CONTENT_PATH .'/', array( 'cgi-bin', '.', '..','._', $restore_file_name, 'status.log', 'db-backup.sql', 'backupsiteinfo.txt')) ) {
	fwrite($fh, '<status code="wpcontent">Done</status>');
} else {
	fwrite($fh, '<status code="errorMessage">Error: Unable to import wp-content. Please try again.</status>');
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}



//Delete the restoration directory
recursive_delete($restoration_dir_path);

//Delete zip
unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);

//close log file
fwrite($fh, '<status code="infomessage">Restoration Complete</status>');
fwrite($fh, '<status code="finalinfo">Finished</status>');
fclose($fh);

//End backup function
die();