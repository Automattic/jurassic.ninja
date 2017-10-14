<?php

/*
 * Plugin Name: Jurassic Ninja
 * Description: Launch ephemeral instances of WordPress + Jetpack using ServerPilot and an Ubuntu Box.
 * Version: 1.0
 * Author: Osk
 **/

namespace jn;

require_once __DIR__ . '/lib/cron-stuff.php';
require_once __DIR__ . '/lib/db-stuff.php';
require_once __DIR__ . '/lib/error-stuff.php';
require_once __DIR__ . '/lib/settings-stuff.php';
require_once __DIR__ . '/lib/stuff.php';

add_options_page();
// Settings problems include credentials and IDs not configured or invalid
if ( ! settings_problems() ) {
	// Include the JS only under the /create route.
	add_scripts();
	// Serve the API root and nonce only under the /create route
	add_rest_nonce();
	// Add /create /checkin and /extend endpoints
	add_rest_api_endpoints();
	add_cron_job();
}

// Yeah create two tables for tracking the launched sites.
create_tables( __FILE__ );
add_error_notices();


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
		if ( 'create' === get_page_uri() ) {
			wp_localize_script( 'jurassicninja.js', 'restApiSettings', array(
				'root' => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			) );
		}
	} );
}
