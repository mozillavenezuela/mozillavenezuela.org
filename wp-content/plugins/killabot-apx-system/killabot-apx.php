<?php
/*   
    Plugin Name: Killabot-APx  
    Plugin URI: http://www.killabot.net  
    Description: Plugin for detecting and blocking access to your Site from Anonymous Proxy users. Remember to select the <a href="options-general.php?page=Killabot-APx">Options Configurations Panel</a> to activate the API Key and enable Anonymous Proxy Protection.
    Author: Mark Patterson  
    
	Version: 1.0.5  
    Author URI: http://www.killabot.net      

 	Copyright© 2009 Mark Patterson email (mark.patterson@killabot.net) 
   
	This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
define("KILLABOT_DIRECTORY", get_option('siteurl').'/wp-content/plugins/killabot-apx-system');
function killabot_js(){?>
<script type="text/javascript">
/* <![CDATA[ */
var killabot_timer;var killabot_api_key;
var killabot_glite = '<?php print get_option('siteurl')?>/wp-content/plugins/killabot-apx-system/images/glite.gif';
var killabot_rlite = '<?php print get_option('siteurl')?>/wp-content/plugins/killabot-apx-system/images/rlite.gif';
var killabot_wlite = '<?php print get_option('siteurl')?>/wp-content/plugins/killabot-apx-system/images/waiting.gif';
var killabot_ajax  = '<?php print get_option('siteurl')?>/wp-admin/admin-ajax.php';
/* ]]> */	
</script>
<?php }
if($_GET['page']=='Killabot-APx'):
	wp_enqueue_script('killabot-apx-popup', get_bloginfo('wpurl') . '/wp-content/plugins/killabot-apx-system/jquery.popupwindow.js',array('jquery'));
	wp_enqueue_script('killabot-apx',get_option('siteurl').'/wp-content/plugins/killabot-apx-system/killabot-apx.js', array('jquery')); 
	add_action('admin_head','killabot_js');
endif;
//===================================
// Utility Functions 
//===================================
function killabot_load_headers() {
	global $sorted;
	if (!is_callable('getallheaders')){
		$headers = array();
		foreach ($_SERVER as $h => $v){
			if (ereg('HTTP_(.+)', $h, $hp)){
				$headers[str_replace("_", "-", killabot_uc_all($hp[1]))] = $v;}
			}
	}else{
		$sorted = true;
		$headers = getallheaders();
	}
	return $headers;
}
function killabot_uc_all($string) {
	$temp = preg_split('/(\W)/', str_replace("_", "-", $string), -1, PREG_SPLIT_DELIM_CAPTURE);
	foreach ($temp as $key=>$word) {
		$temp[$key] = ucfirst(strtolower($word));
	}
	return join ('', $temp);
}
function killabot_GenerateAPIKey($length = 8){
   $key = "";
   $possible = "0123456789bcdfghjkmnpqrstvwxyz"; 
   $i = 0; 
   while ($i < $length) : 
    	$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
    	if(!strstr($key, $char)) :
      		$key .= $char;
      		$i++;
   		 endif;
	endwhile;
  return $key;
}
//===================================
// DB Section 
//===================================
function killabot_DBcheck(){
	global $wpdb;
	if(!$wpdb->get_var(
		$wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."killabot")
	)):
		print 0;
	else:
		print 1;
	endif;
	exit;
}
function killabot_DBInstall(){
	global $wpdb;
	$table = $wpdb->prefix."killabot";
	$key   = killabot_GenerateAPIKey(20);
	$site  = get_option('siteurl');
	
	$drop  = "DROP TABLE IF EXISTS $table;";
	$wpdb->query($drop);
	
	$structure = "CREATE TABLE $table (
			id INT(9) NOT NULL AUTO_INCREMENT,
			api_key VARCHAR(50) NOT NULL,
			reg INT(5) NOT NULL,
			protect INT(5) NOT NULL,
			dom VARCHAR(150) NOT NULL,
			UNIQUE KEY id (id),
			UNIQUE KEY dom (dom));"; 
	$wpdb->query($structure);
	
	$wpdb->insert( $table, array( 
		'api_key' 	=> $key, 
		'reg' 		=> 0,
		'protect' 	=> 0,
		'dom' 		=> $site), array( '%s', '%d', '%d', '%s' ) );
	print 1; 
	exit;
}
function killabot_DBValue($field = ''){
	global $wpdb;
	$field = $_POST['field'];
	$table = $wpdb->prefix."killabot";
	print $wpdb->get_var(
		$wpdb->prepare(
			"SELECT $field FROM $table WHERE (dom = %s)",get_option('siteurl')
		)
	);
	exit;
}
function killabot_DBValueGet($field = ''){
	global $wpdb;
	$table = $wpdb->prefix."killabot";
	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT $field FROM $table WHERE (dom = %s)",get_option('siteurl')
		)
	);
	exit;
}
//===================================
// User Interface:
//===================================
function killabot_UIRegister(){
	global $wpdb;
	$killabot_reg = killabot_DBValueGet('reg');
	if($killabot_reg):
		$killabot_mode = 'verify';
		$killabot_key  = $_POST['key'];
		if(killabot_WSRegistrationService($killabot_mode,$killabot_key)):
			print 1;
		else:
			print 0;
		endif;
	else:
		$killabot_mode = 'register';
		$killabot_key  = killabot_DBValueGet('api_key');
		if(killabot_WSRegistrationService($killabot_mode,$killabot_key)):
			$table = $wpdb->prefix."killabot";
			$sql   = "UPDATE $table SET reg = 1,protect = 1 WHERE api_key = %s";
			$wpdb->query($wpdb->prepare($sql,$killabot_key));
			print 1;
		else:
			print 0;
		endif;
	endif;
	exit;
}
function killabot_UIProtectE(){
	global $wpdb;
	$killabot_key  = killabot_DBValueGet('api_key');
	$table = $wpdb->prefix."killabot";
	$sql   = "UPDATE $table SET protect = 1 WHERE api_key = %s";
	$wpdb->query($wpdb->prepare($sql,$killabot_key));
	print 1;
	exit;	
}
function killabot_UIProtectD(){
	global $wpdb;
	$killabot_key  = killabot_DBValueGet('api_key');
	$table = $wpdb->prefix."killabot";
	$sql   = "UPDATE $table SET protect = 0 WHERE api_key = %s";
	$wpdb->query($wpdb->prepare($sql,$killabot_key));
	print 1;
	exit;	
}
//===================================
// Web Service:
//===================================
function killabot_WSPing(){
	include_once(ABSPATH.'wp-includes/class-IXR.php');
	$killabot_client = new IXR_Client('http://webservice.killabot.net/kbot_server/ping.php');
	if(!$killabot_client->query('killabot.Ping', array('ping'))):
		die('Something went wrong - '.$killabot_client->getErrorCode().' :  '.
		$killabot_client->getErrorMessage());
	endif; 
	print $killabot_client->getResponse();
	exit;
}
function killabot_WSRegistrationService($killabot_mode,$killabot_api_key){
	global $wpdb;
	include_once(ABSPATH.'wp-includes/class-IXR.php');
	$killabot_api_key   = $killabot_api_key;
	$killabot_ip_addr   = $_SERVER['REMOTE_ADDR'];
	$killabot_blog_url  = urlencode(get_bloginfo('home'));
	$killabot_this_page = urlencode("http://".strtolower($_SERVER["HTTP_HOST"]).strtolower($_SERVER["REQUEST_URI"]));
	$killabot_registration_array=array(
		"API Key"  	=> $killabot_api_key, 
		"IP"		=> $killabot_ip_addr,
		"Blog URL"	=> $killabot_blog_url, 
		"Page"		=> $killabot_this_page,
		"Mode"		=> $killabot_mode);
	$killabot_registration_client_array = array();
	foreach($killabot_registration_array as $key => $value):
		$killabot_registration_client_array[] = array(
			"Title" 	=> $key, 
			"Parameter" => $value);
	endforeach;
	$killabot_client = new IXR_Client('http://webservice.killabot.net/kbot_server/registerWP.php');
	if(!$killabot_client->query('registerBlogWP', $killabot_registration_client_array)):
		die('Something went wrong - '.$killabot_client->getErrorCode().' :  '.
		$killabot_client->getErrorMessage());
	endif; 
	return $killabot_client->getResponse();
  	exit;	
}
function killabot_WSAPx($x=''){
	$kbot_page = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
	$kbot_this_page = "http://".strtolower($_SERVER["HTTP_HOST"]).strtolower($_SERVER["REQUEST_URI"]);
	$kbot_uri_array = parse_url($kbot_this_page);
		
	if ( (!is_admin()) && ($kbot_page != 'wp-login.php') && ($kbot_uri_array['query'] != 'doing_wp_cron') ):
		if ( ('POST' == $_SERVER['REQUEST_METHOD']) || ($x == 1) ) :
		    global $wpdb;
		    $killabot_api_enabled = $wpdb->get_var("SELECT protect FROM ".$wpdb->prefix."killabot");
		    if($killabot_api_enabled):
				include_once(ABSPATH.'wp-includes/class-IXR.php');
				$killabot_client = new IXR_Client('http://webservice.killabot.net/kbot_server/balance.php');
				if(!$killabot_client->query('killabot.Balance', array((rand()%4)))):
					die('Something went wrong - '.$killabot_client->getErrorCode().' :  '.
					$killabot_client->getErrorMessage());
				endif; 
				$killabot_balance = $killabot_client->getResponse();
				
				//=================================================
				
				$kbot_head = "";
				$kbot_api_key = killabot_DBValueGet('api_key');
				$kbot_headers = killabot_load_headers();
				if (!is_callable('getallheaders')):
					$sorted = 0;	
				else:
					$sorted = 1;
				endif;
				foreach($kbot_headers as $key => $value):
					$kbot_head .= $key."--".urlencode($value)."|";
				endforeach; 
				$kbot_array = array(
					"API Key"   	=> $kbot_api_key, 
					"IP"			=> $_SERVER['REMOTE_ADDR'],
					"Page"        	=> urlencode($kbot_this_page), 
					"User Agent"  	=> urlencode($_SERVER['HTTP_USER_AGENT']),
					"Headers"     	=> $kbot_head,        
					"Req Method"  	=> $_SERVER['REQUEST_METHOD'],
					"Protocol"     	=> $_SERVER['SERVER_PROTOCOL'],
			        "Port"     		=> $_SERVER['SERVER_PORT'],
					"Sorted"     	=> $sorted); 
				$kbot_p=array();
				foreach($kbot_array as $key => $val):
					$kbot_p[] = array("Title" =>$key,"Parameter" =>$val);
				endforeach; 
				$kbot_client = new IXR_Client("$killabot_balance");
				if(!$kbot_client->query('killabot.securityProxy', $kbot_p)):
					die('Something went wrong - '.$kbot_client->getErrorCode().' :  '.
					$kbot_client->getErrorMessage());
				endif; 
				$kbot_retval = $kbot_client->getResponse();
				if($kbot_retval):
					if($x == 1):
						return true;
					else:
						if($_SERVER['CONTENT_TYPE']!='text/xml'):
							wp_die('RESTRICTED: Anonymous Proxy Usage detected.');
						endif;
					endif;
				else:
					return false;
				endif;
			endif;	
		endif;
	endif;
}
function killabot_WSWarning(){
	if(killabot_WSAPx(1)):
		print '<p><img src="'.KILLABOT_DIRECTORY.'/images/proxy_b.gif" alt="Anonymous Proxy Detected!"/></p>';	
	endif;
}
function killabot_WSDetect(){
	if(killabot_WSAPx(1)):
		print '<img src="'.KILLABOT_DIRECTORY.'/images/proxy_b.gif" alt="Anonymous Proxy Detected!"/>';	
	else:
		print '<img src="'.KILLABOT_DIRECTORY.'/images/proxy_g.gif" alt="Anonymous Proxy Not Detected!"/>';	
	endif;
}
//===================================
// Initialize Admin Page:
//===================================
function killabot_APxConfigActions(){
add_options_page(
	"Killabot-APx", 
	"Killabot-APx", 1, 
	"Killabot-APx", 
	"killabot_APxConfig");
}
function killabot_APxConfig(){
	global $wpdb;
	include 'killabot-apx-html.php';
}
add_action('admin_menu', 		 'killabot_APxConfigActions');
add_action('wp_ajax_db_check',   'killabot_DBcheck');
add_action('wp_ajax_db_install', 'killabot_DBInstall');
add_action('wp_ajax_db_value',   'killabot_DBValue');
add_action('wp_ajax_ws_ping',	 'killabot_WSPing');
add_action('wp_ajax_ui_register','killabot_UIRegister');
add_action('wp_ajax_ui_protectE','killabot_UIProtectE');
add_action('wp_ajax_ui_protectD','killabot_UIProtectD');
add_action('init', 'killabot_WSAPx');?>
