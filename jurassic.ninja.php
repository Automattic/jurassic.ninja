<?php
/**
 * Plugin Name: Jurassic Ninja
 * Description: Launch ephemeral instances of WordPress + Jetpack using ServerPilot and an Ubuntu Box.
 * Version: 5.11.1
 * Author: Automattic
 *
 * @package jurassic-ninja
 **/

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

define( 'PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once __DIR__ . '/lib/error-stuff.php';

init_or_fail_if_no_dependencies_installed();

/**
 * Creates settings page, REST API extensions, cron jobs, and administrative tables.
 * It also adds nonce and javascript to the /create page.
 * Will not load any feature if the settings are not well configured.
 */
function init() {
	require_once __DIR__ . '/lib/cron-stuff.php';
	require_once __DIR__ . '/lib/db-stuff.php';
	require_once __DIR__ . '/lib/settings-stuff.php';
	require_once __DIR__ . '/lib/jetpack-stuff.php';
	require_once __DIR__ . '/lib/stuff.php';

	if ( is_cli_running() ) {
		require_once __DIR__ . '/lib/class-jn-cli-command.php';
	}

	/**
	 * Done before adding settings page or anything else related to Jurassic Ninja Admin specifics.
	 *
	 * It's here so we can hook early to other filters that impace the admin pages like settings.
	 *
	 * @since 3.0
	 */
	do_action( 'jurassic_ninja_admin_init' );
	// Create settings page.
	add_settings_page();
	// Settings problems include credentials and IDs not configured.
	if ( ! settings_problems() ) {
		// Include the JS only under the page which has the /create slug.
		add_scripts();
		// Serve the API root and nonce only under the page which has the /create slug.
		add_rest_nonce();
		// Add wp-json /create /checkin and /extend endpoints.
		add_rest_api_endpoints();
		// Disable temporarily. Run via crontab and Jurassic Ninja's CLI
		// add_cron_job( __FILE__ );.
		add_admin_bar_node();
	}
	/**
	 * Done after adding settings page and before anything else related to Jurassic Ninja specifics.
	 *
	 * It's here so we can hook to other filters after creating the REST API ednpoints, added cron tasks and admi-related stuff
	 *
	 * @since 3.0
	 */
	do_action( 'jurassic_ninja_init' );
	// Yeah create two tables for tracking the launched sites.
	create_tables( __FILE__ );
}

/**
 * Is WP CLI running?
 *
 * @return bool
 */
function is_cli_running() {
	return defined( 'WP_CLI' ) && WP_CLI;
}

/**
 * Checks if the vendor directory is present and just shows a warning and quits if it's not the case.
 * This can probably be removed if it makes sense to just include dependencies.
 */
function init_or_fail_if_no_dependencies_installed() {
	require_once __DIR__ . '/lib/error-stuff.php';

	add_error_notices();
	if ( ! is_dir( __DIR__ . '/vendor' ) ) {
		push_error( new \WP_Error( 'no-dependencies', __( 'Run composer install first', 'jurassic-ninja' ) ) );
	} else {
		init();
	}
}

/**
 * Adds a Topbar link to the Jurassic Ninja sites page.
 */
function add_admin_bar_node() {
	add_action(
		'wp_before_admin_bar_render',
		function () {
			global $wp_admin_bar;

			$wp_admin_bar->add_node(
				array(
					'id'    => 'wp-admin-bar-jurassic-ninja',
					'title' => 'Jurassic Ninja Sites',
					'href'  => admin_url( 'admin.php?page=jurassic_ninja' ),
					'parent' => 'site-name',
				)
			);
		}
	);
}

/**
 * Adds nonce for cookie-based authentication against the REST API extensions
 * that this plugin creates.
 */
function add_rest_nonce() {
	add_action(
		'wp_enqueue_scripts',
		function () {
			// Add the nonce under the /create path and
			// if the user can manage options, add it also on /specialops.
			if ( page_is_launching_page() ) {
				wp_localize_script(
					'jurassicninja.js',
					'restApiSettings',
					array(
						'root' => esc_url_raw( rest_url() ),
						'nonce' => wp_create_nonce( 'wp_rest' ),
					)
				);
			}
		}
	);
}

/**
 * Adds javascript needed by this plugin
 */
function add_scripts() {
	add_action(
		'wp_enqueue_scripts',
		function () {
			if ( page_is_launching_page() ) {
				wp_enqueue_script( 'jurassicninja.js', plugins_url( '', __FILE__ ) . '/jurassicninja.js', array( 'jquery' ), '1.1', true );
				/**
				 * Done after enqueueing the jurassic.ninja.js file
				 *
				 * This action happens during a wp_enqueue_scripts hook.
				 *
				 * @since 3.0
				 */
				do_action( 'jurassic_ninja_enqueue_scripts' );
			}
		}
	);
}

/**
 * Returns true if currently on a /create or /specialops page
 *
 * @return boolean [description]
 */
function page_is_launching_page() {
	return ( 'create' === get_page_uri() || page_is_specialops() );
}

/**
 * Returns true if currently on a /specialops page
 *
 * @return boolean [description]
 */
function page_is_specialops() {
	return current_user_can( 'manage_options' ) && 'specialops' === get_page_uri();
}
