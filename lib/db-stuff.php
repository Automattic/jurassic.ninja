<?php
/**
 * DB stuff.
 *
 * @package jurassic-ninja
 */

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

/**
 * Returns the $wpdb global.
 *
 * @return wpdb WP DB object.
 */
function db() {
	global $wpdb;
	return $wpdb;
}

/**
 * Used by the main plugin file to register a hook
 * for the activation process.
 * It will create a few tables needed for janitorial matters
 *
 * @param  string $plugin_file Nothing super useful. the plugin path.
 */
function create_tables( $plugin_file ) {
	register_activation_hook( $plugin_file, 'jn\jurassic_ninja_create_table' );
}

/**
 * Creates tables.
 */
function jurassic_ninja_create_table() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$sites_sql = "CREATE TABLE sites (
		`id` INT NOT NULL AUTO_INCREMENT,
		username text not null,
		password text not null,
		domain text not null,
		created datetime ,
		last_logged_in datetime,
		checked_in datetime,
		shortlived boolean not null DEFAULT 0,
		launched_by text not null,
		PRIMARY KEY  (id)
	) $charset_collate;";

	$purged_sites_sql = "CREATE TABLE purged (
		`id` INT NOT NULL AUTO_INCREMENT,
		username text not null,
		domain text not null,
		created datetime ,
		last_logged_in datetime,
		checked_in datetime,
		shortlived boolean not null DEFAULT 0,
		launched_by text not null,
		PRIMARY KEY  (id)
	) $charset_collate;";

	$spare_sites_sql = "CREATE TABLE spare_sites (
		`id` INT NOT NULL AUTO_INCREMENT,
		username text not null,
		password text not null,
		domain text not null,
		created datetime,
		app_id text not null,
		locked_by text not null,
		PRIMARY KEY  (id)
	) $charset_collate;";

	if ( ! function_exists( 'dbDelta' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	try {
		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		@dbDelta( $sites_sql );
		@dbDelta( $purged_sites_sql );
		@dbDelta( $spare_sites_sql );
		// phpcs:enable

	} catch ( \Exception $e ) {
		push_error( new \WP_Error( 'error_creating_administartive_tables', $e->getMessage() ) );
	}
}
