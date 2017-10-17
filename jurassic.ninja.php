<?php

/*
 * Plugin Name: Jurassic Ninja
 * Description: Launch ephemeral instances of WordPress + Jetpack using ServerPilot and an Ubuntu Box.
 * Version: 1.0
 * Author: Osk
 **/

namespace jn;

require_once __DIR__ . '/lib/error-stuff.php';

add_error_notices();

init_or_fail_if_no_dependencies_installed();

/**
 * Checks if the vendor directory is present and just shows a warning and quits if it's not the case.
 * This can probably be removed if it makes sense to just include dependencies.
 */
function init_or_fail_if_no_dependencies_installed() {
	if ( ! is_dir( __DIR__ . '/vendor' ) ) {
		push_error( new \WP_Error( 'no-dependencies', __( 'Run composer install first' ) ) );
	} else {
		init();
	}
}

/**
 * Creates settings page, REST API extensions, cron jobs, and administrative tables.
 * It also adds nonce and javascript to the /create page.
 * Will not load any feature if the settings are not well configured.
 *
 * @return [type] [description]
 */
function init() {
	require_once __DIR__ . '/lib/cron-stuff.php';
	require_once __DIR__ . '/lib/db-stuff.php';
	require_once __DIR__ . '/lib/serverpilot-stuff.php';
	require_once __DIR__ . '/lib/settings-stuff.php';
	require_once __DIR__ . '/lib/stuff.php';

	//Create settings page
	add_options_page();
	// Settings problems include credentials and IDs not configured
	if ( ! settings_problems() ) {
		// Include the JS only under the page which has the /create slug.
		add_scripts();
		// Serve the API root and nonce only under the page which has the /create slug
		add_rest_nonce();
		// Add wp-json /create /checkin and /extend endpoints
		add_rest_api_endpoints();
		add_cron_job();
		add_admin_bar_node();
	}

	// Yeah create two tables for tracking the launched sites.
	create_tables( __FILE__ );
}

/**
* Adds javascript needed by this plugin
*/
function add_scripts() {
	add_action( 'wp_enqueue_scripts', function () {
		wp_enqueue_script( 'jurassicninja.js', plugins_url( '', __FILE__ ) . '/jurassicninja.js', false, false, true );
	} );
}

function add_rest_nonce() {
	add_action( 'wp_enqueue_scripts', function() {
		// Add the nonce under the /create path and
		// if the user is admin, add it also on /specialops
		if ( 'create' === get_page_uri()
			|| ( current_user_can( 'manage_options' ) && 'specialops' === get_page_uri() ) ) {

			wp_localize_script( 'jurassicninja.js', 'restApiSettings', array(
				'root' => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			) );
		}
	} );
}

function add_admin_bar_node() {
	add_action( 'wp_before_admin_bar_render', function () {
		global $wp_admin_bar;

		$wp_admin_bar->add_node(array(
			'id'    => 'wp-admin-bar-jurassic-ninja',
			'title' => 'Jurassic Ninja Sites',
			'href'  => admin_url( 'admin.php?page=jurassic_ninja' ),
			'parent' => 'site-name',
		));
	} );
}


