<?php
/*
Plugin Name: Wordpress Code Snippet
Plugin URI: http://www.allancollins.net/486/wordpress-code-snippet-2-0/
Description: Add code snippets to pages and posts.  Excellent for tutorial sites.
Author: Allan Collins
Version: 2.0.3
Author URI: http://www.allancollins.net
*/
require_once("wcs.php");
$wcs=new wcs();

/* WP Actions/Filters */
add_filter('the_content', array($wcs,'contentFilter'));
add_action('admin_menu', array($wcs,'setupPages'));
add_action('admin_head', array($wcs,'adminHead'));
add_action('admin_print_scripts', array($wcs,'adminInit'));
add_action('wp_head', array($wcs,'pageHead'));
add_action('wp_footer', array($wcs,'pageFoot'));
add_action('init', array($wcs,'ajax'));

register_activation_hook( __FILE__, 'wcsCredit' );

function wcsCredit(){
    update_option('wcsLink',false);
}
/*
 * @todo TinyMCE Integration
 */
//add_action('init', array($wcs,'addButtons'));


?>