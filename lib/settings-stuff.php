<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

define( 'SETTINGS_KEY', 'jurassic-ninja-settings' );

/**
 * Access a plugin option
 * @param  String $key The particular option we want to access
 * @param  Mixed  $default As with get_option you can specify a defaul value to return if the option is not set
 * @return String      The option value. All of the are just strings.
 */
function settings( $key = null, $default = null ) {
	$options = get_option( SETTINGS_KEY );

	// Create the array needed by ServerPilot() here so I don't have to copy/paste this around
	if ( 'serverpilot' === $key ) {
		return [
			'id' => $options['serverpilot_client_id'],
			'key' => $options['serverpilot_client_key'],
		];
	}

	if ( ! isset( $options[ $key ] ) ) {
		return func_num_args() === 2 ? $default : null;
	}

	return $options[ $key ];
}

/**
 * Checks if the settings are not blank and returns an array informing
 * which ones are not configured yet.
 *
 * @return Array problems found when checking settings.
 */
function settings_problems() {
	$unconfigured = [];

	if ( ! settings( 'serverpilot_client_key' ) ) {
		$unconfigured[] = __( 'ServerPilot Client Key', 'jurassic-ninja' );
	};
	if ( ! settings( 'serverpilot_client_id' ) ) {
		$unconfigured[] = __( 'ServerPilot Client Id', 'jurassic-ninja' );
	};

	if ( ! settings( 'serverpilot_server_id' ) ) {
		$unconfigured[] = __( 'ServerPilot Server Id', 'jurassic-ninja' );
	};

	if ( ! settings( 'domain' ) ) {
		$unconfigured[] = __( 'Parent Domain', 'jurassic-ninja' );
	};

	if ( ! settings( 'default_admin_email_address' ) ) {
		$unconfigured[] = __( 'Main Admin Email Address', 'jurassic-ninja' );
	};

	// Comment this out until I find a better way to do this without querying
	// ServerPilot's API on each page load :troll:
	// $serverpilot_settings_set = settings( 'serverpilot_client_key' ) && settings( 'serverpilot_client_id' )
	// 	&& settings( 'serverpilot_server_id' );
	// if ( $serverpilot_settings_set ) {
	// 	try {
	// 		sp()->server_info( settings( 'serverpilot_server_id' ) );
	// 	} catch ( \ServerPilotException $e ) {
	// 		$unconfigured[] = __( 'valid ServerPilot Id, Key and Server Id for a paid plan', 'jurassic-ninja' );
	// 	}
	// }

	return $unconfigured;
}

/**
 * Creates two pages for the plugin
 *     - The options page
 *     - The Site Admin page
 */
function add_settings_page() {
	$options_page = [
		'jurassic-ninja' => array(
			'page_title' => __( 'Jurassic Ninja Sites Admin', 'jurassic-ninja' ),
			'menu_title' => __( 'Jurassic Ninja', 'jurassic-ninja' ),
			'icon_url' => 'dashicons-tickets',
			'menu_slug' => 'jurassic_ninja',
			'sections' => array(
				'section-one' => array(
					'title' => __( 'Launched sites', 'jurassic-ninja' ),
					'include' => plugin_dir_path( __FILE__ ) . 'views/sites.php',
				),
			),
		),
		SETTINGS_KEY => array(
			'page_title' => __( 'Jurassic Ninja Settings', 'jurassic-ninja' ),
			'menu_title' => __( 'Settings', 'jurassic-ninja' ),
			'menu_slug' => 'jurassic_ninja_settings',
			'parent_slug' => 'jurassic_ninja',
			'sections' => array(
				'domain' => array(
					'title' => __( 'Sites', 'jurassic-ninja' ),
					'text' => '<p>' . __( 'Configure some defaults for the launched sites here.', 'jurassic-ninja' ) . '</p>',
					'fields' => array(
						'domain' => array(
							'id' => 'domain',
							'title' => __( 'The parent domain for each launched WordPress', 'jurassic-ninja' ),
							'text' => __( 'Every created site will be created with a subodmain abcd.jurassic.ninja', 'jurassic-ninja' ),
							'placeholder' => 'jurassic.ninja',
							'value' => 'jurassic.ninja',
						),
						'default_admin_email_address' => array(
							'id' => 'default_admin_email_address',
							'title' => __( 'Default Admin Email Address for each launched WordPress', 'jurassic-ninja' ),
							'type' => 'email',
							'placeholder' => 'test@test.com',
							'value' => 'test@test.com',
						),
						'sites_expiration' => array(
							'id' => 'sites_expiration',
							'title' => __( 'Sites lifespan', 'jurassic-ninja' ),
							'text' => __( 'Default interval for considering a site to be expired. Expressed in MySQL interval format.', 'jurassic-ninja' ),
							'placeholder' => 'INTERVAL 7 DAY',
							'value' => 'INTERVAL 7 DAY',
						),
						'shortlived_sites_expiration' => array(
							'id' => 'shortlived_sites_expiration',
							'title' => __( 'Shortlived sites lifespan', 'jurassic-ninja' ),
							'text' => __( 'Default interval for considering a site to be expired if it has been created a shortlived site. Expressed in MySQL interval format.', 'jurassic-ninja' ),
							'placeholder' => 'INTERVAL 1 HOUR',
							'value' => 'INTERVAL 1 HOUR',
						),
						'sites_never_checked_in_expiration' => array(
							'id' => 'sites_never_checked_in_expiration',
							'title' => __( 'Unvisited sites lifespan', 'jurassic-ninja' ),
							'text' => __( 'Default interval for considering a site to be expired if the admin never visited wp-admin. Expressed in MySQL interval format.', 'jurassic-ninja' ),
							'placeholder' => 'INTERVAL 2 HOUR',
							'value' => 'INTERVAL 2 HOUR',
						),
						'use_subdomain_based_wordpress_title' => array(
							'id' => 'use_subdomain_based_wordpress_title',
							'title' => __( 'Base the site titles on their subdomain', 'jurassic-ninja' ),
							'text' => __( 'Example: http://ugly-unicorn.domain will be titled "Ugly Unicorn".', 'jurassic-ninja' ),
							'type' => 'checkbox',
							'checked' => false,
						),
						'use_alliterations_for_subdomain' => array(
							'id' => 'use_alliterations_for_subdomain',
							'title' => __( 'Base the site domain just on alliterations', 'jurassic-ninja' ),
							'text' => __( 'Example: ugly-unicorn.domain and not fancy-unicorn.domain.', 'jurassic-ninja' ),
							'type' => 'checkbox',
							'checked' => true,
						),
						'set_wp_debug_log_by_default' => array(
							'id' => 'set_wp_debug_log_by_default',
							'title' => __( 'Set WP_DEBUG and WP_DEBUG_LOG to true on every launched WordPress', 'jurassic-ninja' ),
							'text' => __( 'Enabling debugging log on launch', 'jurassic-ninja' ),
							'type' => 'checkbox',
							'checked' => false,
						),
						'purge_sites_when_cron_runs' => array(
							'id' => 'purge_sites_when_cron_runs',
							'title' => __( 'Run the cron task that purges sites when they expire  (Hourly)', 'jurassic-ninja' ),
							'text' => __( 'Purge sites when they expire', 'jurassic-ninja' ),
							'type' => 'checkbox',
							'checked' => true,
						),
						'enable_launching' => array(
							'id' => 'enable_launching',
							'title' => __( 'Enable the launching of sites', 'jurassic-ninja' ),
							'text' => __( 'Enable sites launching', 'jurassic-ninja' ),
							'type' => 'checkbox',
							'checked' => true,
						),
						'lock_launching' => array(
							'id' => 'lock_launching',
							'title' => __( 'Restrict launching of sites to authenticated users only', 'jurassic-ninja' ),
							'text' => __( 'Only registered users can launch sites', 'jurassic-ninja' ),
							'type' => 'checkbox',
							'checked' => false,
						),
						'log_debug_messages' => array(
							'id' => 'log_debug_messages',
							'title' => __( 'Log debug messages if WP_DEBUG is on', 'jurassic-ninja' ),
							'text' => __( 'Log debug messages', 'jurassic-ninja' ),
							'type' => 'checkbox',
							'checked' => false,
						),
					),
				),
			),
		),
	];
	/**
	 * Filters the argument passed to the RationalOptionPages constructor
	 *
	 * @since 3.0
	 *
	 * @param array  $options_page The options page key/values for RationalOptionPages::__contruct()
	 */
	new \RationalOptionPages( apply_filters( 'jurassic_ninja_settings_options_page', $options_page ) );
}
