<?php

namespace jn;

/**
 * Access a plugin option
 * @param  string $key The particular option we want to access
 * @return string      The option value. All of the are just strings.
 */
function config( $key = null, $default = null ) {
	$options = get_option( OPTIONS_KEY );

	if ( ! ( $options ) ) {
		throw new \Exception( 'Error Finding config variable', 1 );
	}

	// Create the array needed by ServerPilot() here so I don't have to copy/paste this around
	if ( 'serverpilot' === $key ) {
		return [
			'id' => config( 'serverpilot_client_id' ),
			'key' => config( 'serverpilot_client_key' ),
		];
	}

	if ( ! isset( $options[ $key ] ) ) {
		return func_num_args() === 2 ? $default : null ;
	}

	return $options[ $key ];
}

/**
 * Creates two pages for the plugin
 *     - The options page
 *     - The Site Admin page
 */
function add_options_page() {
	$options_page = new \RationalOptionPages( [
		'jurassic-ninja' => array(
			'page_title' => __( 'Jurassic Ninja Settings', 'jurassic-ninja' ),
			'menu_slug' => 'jurassic_ninja',
			'sections' => array(
				'domain' => array(
					'title' => __( 'Sites', 'jurassic-ninja' ),
					'text' => '<p>' . __( 'Configure some defaults for the launched sites here.', 'jurassic-ninja' ) . '</p>',
					'fields' => array(
						'domain' => array(
							'id' => 'domain',
							'title' => __( 'The parent domain for each launched WordPress', 'jurassic-ninja' ),
							'text' => __( 'Every created site will be created with a subodmain abcd.jurassic.ninja' ),
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
						'sites_never_checked_in_expiration' => array(
							'id' => 'sites_never_checked_in_expiration',
							'title' => __( 'Unvisited sites lifespan', 'jurassic-ninja' ),
							'text' => __( 'Default interval for considering a site to be expired if the admin never visited wp-admin. Expressed in MySQL interval format.', 'jurassic-ninja' ),
							'placeholder' => 'INTERVAL 2 HOUR',
							'value' => 'INTERVAL 2 HOUR',
						),
						'add_jetpack_by_default' => array(
							'id' => 'add_jetpack_by_default',
							'title' => __( 'Add Jetpack to every launched WordPress', 'jurassic-ninja' ),
							'text' => __( 'Install and activate Jetpack on launch', 'jurassic-ninja' ),
							'type' => 'checkbox',
							'checked' => true,
						),
						'add_jetpack_beta_by_default' => array(
							'id' => 'add_jetpack_beta_by_default',
							'title' => __( 'Add Jetpack Beta Tester plugin to every launched WordPress', 'jurassic-ninja' ),
							'text' => __( 'Install and activate Jetpack Beta Tester on launch', 'jurassic-ninja' ),
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
					),
				),
				'serverpilot' => array(
					'title' => __( 'ServerPilot Configuration', 'jurassic-ninja' ),
					'text' => '<p>' . __( 'Configure ServerPilot client Id and Key. This need to be one of the paid plans. At least a Coach Plan', 'jurassic-ninja' ) . '</p>',
					'fields' => array(
						'serverpilot_server_id' => array(
							'id' => 'serverpilot_server_id',
							'title' => __( 'ServerPilot Server Id', 'jurassic-ninja' ),
							'text' => __( 'A ServerPilot Server Id.' ),
						),
						'serverpilot_client_id' => array(
							'id' => 'serverpilot_client_id',
							'title' => __( 'ServerPilot Client Id', 'jurassic-ninja' ),
							'text' => __( 'A ServerPilot Client id.' ),
						),
						'serverpilot_client_key' => array(
							'id' => 'serverpilot_client_key',
							'title' => __( 'ServerPilot Key', 'jurassic-ninja' ),
							'text' => __( 'A ServerPilot Client key.' ),
						),
					),
				),
			),
		),
		'jurassic_ninja_sites_admin' => array(
			'page_title' => __( 'Sites Admin', 'jurassic_ninja' ),
			'parent_slug' => 'jurassic_ninja',
			'sections' => array(
				'section-one' => array(
					'title' => __( 'Launched sites', 'jurassic_ninja' ),
					'include' => plugin_dir_path( __FILE__ ) . 'views/sites.php',
				),
			),
		),
	] );
}
