<?php

namespace jn;

function db() {
	global $wpdb;
	return $wpdb;
}

/**
 * Used by the main plugin file to register a hook
 * for the activation process.
 * It will create a few tables needed for janitorial matters
 *
 * @param  string $plugin_file Naothing super useful. the plugin path
 * @return [type]              [description]
 */
function create_tables( $plugin_file ) {
	register_activation_hook( $plugin_file, 'jn\prefix_create_table' );
}


function prefix_create_table() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE sites (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		username text not null,
		domain text not null,
		created datetime ,
		last_logged_in datetime ,
		checked_in datetime
	) $charset_collate;";

	$sql2 = "CREATE TABLE purged (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		username text not null,
		domain text not null,
		created datetime ,
		last_logged_in datetime ,
		checked_in datetime
	) $charset_collate;";

	if ( ! function_exists( 'dbDelta' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	}

	dbDelta( $sql );
	dbDelta( $sql2 );
}
