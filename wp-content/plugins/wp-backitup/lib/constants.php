<?php
/**
 * Constants used by this plugin
 * 
 * @package WP Backitup Pro
 * 
 * @author jcpeden
 * @version 1.3.0
 * @since 1.0.1
 */

if( !defined( 'WPBACKITUP_VERSION' ) ) define( 'WPBACKITUP_VERSION', '1.3.0' );

if( !defined( 'WPBACKITUP_DIRNAME' ) ) define( 'WPBACKITUP_DIRNAME', dirname( dirname( __FILE__ ) ) );

if( !defined( 'WPBACKITUP_DIR_PATH' ) ) define( 'WPBACKITUP_DIR_PATH', dirname( dirname( dirname( __FILE__ ) ) ) );

if( !defined( 'WPBACKITUP_URLPATH' ) ) define( 'WPBACKITUP_URLPATH', WP_PLUGIN_URL . "/" . plugin_basename( WPBACKITUP_DIRNAME ) );

if( !defined( 'IS_AJAX_REQUEST' ) ) define( 'IS_AJAX_REQUEST', ( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) );

if( !defined( 'WPBACKITUP_SITE_URL' ) ) define( 'WPBACKITUP_SITE_URL', 'http://www.wpbackitup.com' ); 
 
if( !defined( 'WPBACKITUP_ITEM_NAME' ) ) define( 'WPBACKITUP_ITEM_NAME', 'WP Backitup' ); 