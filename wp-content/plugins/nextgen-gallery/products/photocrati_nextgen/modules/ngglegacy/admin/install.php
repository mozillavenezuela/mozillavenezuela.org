<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**
 * creates all tables for the gallery
 * called during register_activation hook
 *
 * @access internal
 * @return void
 */
function nggallery_install () {

   	global $wpdb , $wp_roles, $wp_version;

	// Check for capability
	if ( !current_user_can('activate_plugins') )
		return;

	// Set the capabilities for the administrator
	$role = get_role('administrator');
	// We need this role, no other chance
	if ( empty($role) ) {
		update_option( "ngg_init_check", __('Sorry, NextGEN Gallery works only with a role called administrator',"nggallery") );
		return;
	}

	$role->add_cap('NextGEN Gallery overview');
	$role->add_cap('NextGEN Use TinyMCE');
	$role->add_cap('NextGEN Upload images');
	$role->add_cap('NextGEN Manage gallery');
	$role->add_cap('NextGEN Manage tags');
	$role->add_cap('NextGEN Manage others gallery');
	$role->add_cap('NextGEN Edit album');
	$role->add_cap('NextGEN Change style');
	$role->add_cap('NextGEN Change options');
	$role->add_cap('NextGEN Attach Interface');

	// upgrade function changed in WordPress 2.3
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	// add charset & collate like wp core
	$charset_collate = '';

	if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";
	}

   	$nggpictures					= $wpdb->prefix . 'ngg_pictures';
	$nggallery						= $wpdb->prefix . 'ngg_gallery';
	$nggalbum						= $wpdb->prefix . 'ngg_album';

	// Create pictures table
	$sql = "CREATE TABLE " . $nggpictures . " (
	pid BIGINT(20) NOT NULL AUTO_INCREMENT ,
	image_slug VARCHAR(255) NOT NULL ,
	post_id BIGINT(20) DEFAULT '0' NOT NULL ,
	galleryid BIGINT(20) DEFAULT '0' NOT NULL ,
	filename VARCHAR(255) NOT NULL ,
	description MEDIUMTEXT NULL ,
	alttext MEDIUMTEXT NULL ,
	imagedate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	exclude TINYINT NULL DEFAULT '0' ,
	sortorder BIGINT(20) DEFAULT '0' NOT NULL ,
	meta_data LONGTEXT,
	PRIMARY KEY  (pid),
	KEY post_id (post_id)
	) $charset_collate;";
	dbDelta($sql);

	// Create gallery table
	$sql = "CREATE TABLE " . $nggallery . " (
	gid BIGINT(20) NOT NULL AUTO_INCREMENT ,
	name VARCHAR(255) NOT NULL ,
	slug VARCHAR(255) NOT NULL ,
	path MEDIUMTEXT NULL ,
	title MEDIUMTEXT NULL ,
	galdesc MEDIUMTEXT NULL ,
	pageid BIGINT(20) DEFAULT '0' NOT NULL ,
	previewpic BIGINT(20) DEFAULT '0' NOT NULL ,
	author BIGINT(20) DEFAULT '0' NOT NULL  ,
	PRIMARY KEY  (gid)
	) $charset_collate;";
	dbDelta($sql);

	// Create albums table
	$sql = "CREATE TABLE " . $nggalbum . " (
	id BIGINT(20) NOT NULL AUTO_INCREMENT ,
	name VARCHAR(255) NOT NULL ,
	slug VARCHAR(255) NOT NULL ,
	previewpic BIGINT(20) DEFAULT '0' NOT NULL ,
	albumdesc MEDIUMTEXT NULL ,
	sortorder LONGTEXT NOT NULL,
	pageid BIGINT(20) DEFAULT '0' NOT NULL,
	PRIMARY KEY  (id)
	) $charset_collate;";
	dbDelta($sql);

	// check one table again, to be sure
	if( !$wpdb->get_var( "SHOW TABLES LIKE '$nggpictures'" ) ) {
		update_option( "ngg_init_check", __('NextGEN Gallery : Tables could not created, please check your database settings',"nggallery") );
		return;
	}

	$options = get_option('ngg_options');

	// if all is passed , save the DBVERSION
	add_option("ngg_db_version", NGG_DBVERSION);

}

/**
 * Deregister a capability from all classic roles
 *
 * @access internal
 * @param string $capability name of the capability which should be deregister
 * @return void
 */
function ngg_remove_capability($capability){
	// this function remove the $capability only from the classic roles
	$check_order = array("subscriber", "contributor", "author", "editor", "administrator");

	foreach ($check_order as $role) {

		$role = get_role($role);
		$role->remove_cap($capability) ;
	}

}

/**
 * Uninstall all settings and tables
 * Called via Setup and register_unstall hook
 *
 * @access internal
 * @return void
 */
function nggallery_uninstall() {
	global $wpdb;
	
	// TODO don't remove data on uninstall
	// first remove all tables
#	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ngg_pictures");
#	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ngg_gallery");
#	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ngg_album");

	// then remove all options
	delete_option( 'ngg_options' );
	delete_option( 'ngg_db_version' );
	delete_option( 'ngg_update_exists' );
	delete_option( 'ngg_next_update' );

	// now remove the capability
	ngg_remove_capability("NextGEN Gallery overview");
	ngg_remove_capability("NextGEN Use TinyMCE");
	ngg_remove_capability("NextGEN Upload images");
	ngg_remove_capability("NextGEN Manage gallery");
	ngg_remove_capability("NextGEN Edit album");
	ngg_remove_capability("NextGEN Change style");
	ngg_remove_capability("NextGEN Change options");
	ngg_remove_capability("NextGEN Attach Interface");
}
