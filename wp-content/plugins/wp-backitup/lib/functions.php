<?php
/**
 * WP Backitup Functions
 * 
 * @package WP Backitup
 * 
 * @author jcpeden
 * @version 1.3.0
 * @since 1.0.1
 */

// localize the plugin
function lang_setup() {
	global $WPBackitup;
    load_plugin_textdomain($WPBackitup->namespace, false, dirname(plugin_basename(__FILE__)) . '/lang/');
} 
add_action('after_setup_theme', 'lang_setup');

// include recurseZip class
if( !class_exists( 'recurseZip' ) ) {
	include_once 'includes/recurse_zip.php';
}

// include auto-update class
if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	include_once 'includes/auto_update.php';
}

// retrieve our license key from the DB
$license_key = trim( $this->get_option( 'license_key' ) );

// setup the updater
$edd_updater = new EDD_SL_Plugin_Updater( WPBACKITUP_SITE_URL, dirname ( dirname ( __FILE__) ) .'/index.php', array( 
		'version' 	=> WPBACKITUP_VERSION, 		// current version number
		'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
		'item_name'     => WPBACKITUP_ITEM_NAME, 	// name of this plugin
		'author' 	=> 'John Peden'  // author of this plugin
	)
);

//load backup function
function backup() {
	include_once 'includes/backup.php';
}
add_action('wp_ajax_backup', 'backup');

//load restore_path function
function restore_path() {
	include_once 'includes/restore_from_path.php';
}
add_action('wp_ajax_restore_path', 'restore_path');

//load download function
function download() {
	if(glob(WPBACKITUP_DIRNAME . "/backups/*.zip")) {
		foreach (glob(WPBACKITUP_DIRNAME . "/backups/*.zip") as $file) {
			$filename = basename($file);
			echo 'Download most recent export file: <a href="' .WPBACKITUP_URLPATH. '/backups/' .$filename .'">' .$filename .'</a>'; 
		}
	} else {
		echo 'No export file available for download. Please create one.';
	}
	die();
}
add_action('wp_ajax_download', 'download');

//load logreader function
function logreader() {
	$log = WPBACKITUP_DIRNAME .'/logs/status.log';
	if(file_exists($log) ) {
		readfile($log);
	}
	die();
}
add_action('wp_ajax_logreader', 'logreader');

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

//Define DB backup function
if(!function_exists('db_backup')) {
	function db_backup($path) { 
           
            $handle = fopen($path .'db-backup.sql', 'w+');
            
            
            $path_sql = $path .'/db-backup.sql';
            $db_name = DB_NAME; 
            $db_user  = DB_USER;
            $db_pass = DB_PASSWORD; 
            $db_host = DB_HOST;

            $output = shell_exec("mysqldump --user $db_user --password=$db_pass $db_name");
            fwrite($handle,$output);
            fclose($handle);
            return true;
	}
}

//Define the create_siteinfo function
if(!function_exists('create_siteinfo')) {
	function create_siteinfo($path, $table_prefix) {
		$siteinfo = $path ."backupsiteinfo.txt"; 
		$handle = fopen($siteinfo, 'w+');
		$entry = site_url( '/' ) ."\n$table_prefix";
		fwrite($handle, $entry); 
		fclose($handle); 
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
						if(!is_dir($dir .'/'. $file)) {
							unlink($dir .'/'. $file);
						} else {
							recursive_delete($dir.'/'. $file, $ignore);
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

//Define zip function
function zip($source, $destination, $ignore) {
    if (is_string($source)) $source_arr = array($source); // convert it to array
    if (!extension_loaded('zip')) {
        return false;
    }
    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }
    foreach ($source_arr as $source) {
        if (!file_exists($source)) continue;
		$source = str_replace('\\', '/', realpath($source));
		if (is_dir($source) === true) {
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($files as $file) {
					if (!preg_match($ignore, $file)) {
					$file = str_replace('\\', '/', realpath($file));
					if (is_dir($file) === true) {
						$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
					} else if (is_file($file) === true) {
						$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
					}
				}
			}
		} else if (is_file($source) === true) {
			$zip->addFromString(basename($source), file_get_contents($source));
		}
    }
    return $zip->close();
}

//load presstrends
function load_presstrends() {
	global $WPBackitup;
	if($WPBackitup->get_option( 'presstrends' ) == 'enabled') {
		// PressTrends Account API Key
		$api_key = 'rwiyhqfp7eioeh62h6t3ulvcghn2q8cr7j5x';
		$auth    = 'lpa0nvlhyzbyikkwizk4navhtoaqujrbw';

		// Start of Metrics
		global $wpdb;
		$data = get_transient( 'presstrends_cache_data' );
		if ( !$data || $data == '' ) {
			$api_base = 'http://api.presstrends.io/index.php/api/pluginsites/update/auth/';
			$url      = $api_base . $auth . '/api/' . $api_key . '/';

			$count_posts    = wp_count_posts();
			$count_pages    = wp_count_posts( 'page' );
			$comments_count = wp_count_comments();

			// wp_get_theme was introduced in 3.4, for compatibility with older versions, let's do a workaround for now.
			if ( function_exists( 'wp_get_theme' ) ) {
				$theme_data = wp_get_theme();
				$theme_name = urlencode( $theme_data->Name );
			} else {
				$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
				$theme_name = $theme_data['Name'];
			}

			$plugin_name = '&';
			foreach ( get_plugins() as $plugin_info ) {
				$plugin_name .= $plugin_info['Name'] . '&';
			}
			// CHANGE __FILE__ PATH IF LOCATED OUTSIDE MAIN PLUGIN FILE
			$plugin_data         = get_plugin_data( __FILE__ );
			$posts_with_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='post' AND comment_count > 0" );
			$data                = array(
				'url'             => stripslashes( str_replace( array( 'http://', '/', ':' ), '', site_url() ) ),
				'posts'           => $count_posts->publish,
				'pages'           => $count_pages->publish,
				'comments'        => $comments_count->total_comments,
				'approved'        => $comments_count->approved,
				'spam'            => $comments_count->spam,
				'pingbacks'       => $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback'" ),
				'post_conversion' => ( $count_posts->publish > 0 && $posts_with_comments > 0 ) ? number_format( ( $posts_with_comments / $count_posts->publish ) * 100, 0, '.', '' ) : 0,
				'theme_version'   => $plugin_data['Version'],
				'theme_name'      => $theme_name,
				'site_name'       => str_replace( ' ', '', get_bloginfo( 'name' ) ),
				'plugins'         => count( get_option( 'active_plugins' ) ),
				'plugin'          => urlencode( $plugin_name ),
				'wpversion'       => get_bloginfo( 'version' ),
			);

			foreach ( $data as $k => $v ) {
				$url .= $k . '/' . $v . '/';
			}
			wp_remote_get( $url );
			set_transient( 'presstrends_cache_data', $data, 60 * 60 * 24 );
		}
	}
}
// PressTrends WordPress Action
add_action('admin_init', 'load_presstrends');
