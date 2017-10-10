<?php

namespace jn;

function add_options_page() {
	$options_page = new \RationalOptionPages( [
		'jurassic-ninja' => array(
			'page_title' => __( 'Jurassic Ninja Settings', 'jurassic-ninja' ),
			'menu_slug' => 'jurassic_ninja',
			'sections' => array(
				'domain' => array(
					'title' => __( 'Sites', 'jurassic-ninja' ),
					'text' => '<p>' . __( 'Configure ServerPilot client Id and Key', 'jurassic-ninja' ) . '</p>',
					'fields' => array(
						'domain' => array(
							'id' => 'domain',
							'title' => __( 'The main domain for your WordPresses', 'jurassic-ninja' ),
							'text' => __( 'Every created site will be created with a subodmain xxx.jurassic.ninja' ),
							'placeholder' => 'jurassic.ninja',
						),
						'default_admin_email_address' => array(
							'id' => 'default_admin_email_address',
							'title' => __( 'Default Admin Email Address for each WordPress', 'sample-domain' ),
							'type' => 'email',
							'placeholder' => 'test@test.com',
						),
						'sites_expiration' => array(
							'id' => 'sites_expiration',
							'title' => __( 'Sites lifespan', 'sample-domain' ),
							'text' => __( 'Default interval for considering a site to be expired', 'sample-domain' ),
							'placeholder' => 'INTERVAL 7 DAY',
						),
						'sites_never_logged_in_expiration' => array(
							'id' => 'sites_never_logged_in_expiration',
							'title' => __( 'Unlogged sites lifespan', 'sample-domain' ),
							'text' => __( 'Default interval for considering a site to be expired if the admin never logged in again wp-admin', 'sample-domain' ),
							'placeholder' => 'INTERVAL 7 DAY',
						),
						'sites_never_checked_in_expiration' => array(
							'id' => 'sites_never_checked_in_expiration',
							'title' => __( 'Unvisited sites lifespan', 'sample-domain' ),
							'text' => __( 'Default interval for considering a site to be expired if the admin never visited wp-admin', 'sample-domain' ),
							'placeholder' => 'INTERVAL 2 HOUR',
						),
					),
				),
				'serverpilot' => array(
					'title' => __( 'ServerPilot Configuration', 'jurassic-ninja' ),
					'text' => '<p>' . __( 'Configure ServerPilot client Id and Key', 'jurassic-ninja' ) . '</p>',
					'fields' => array(
						'serverpilot_server_id' => array(
							'id' => 'serverpilot_server_id',
							'title' => __( 'ServerPilot Server Id', 'jurassic-ninja' ),
							'text' => __( 'Your ServerPilot Server.' ),
						),
						'serverpilot_client_id' => array(
							'id' => 'serverpilot_client_id',
							'title' => __( 'ServerPilot Client Id', 'jurassic-ninja' ),
							'text' => __( 'Your ServerPilot Client id.' ),
						),
						'serverpilot_client_key' => array(
							'id' => 'serverpilot_client_key',
							'title' => __( 'ServerPilot Key', 'jurassic-ninja' ),
							'text' => __( 'Your ServerPilot Client key.' ),
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
					'title' => __( 'Section One', 'jurassic_ninja' ),
					'text' => '<p>' . __( 'All about the sites', 'jurassic_ninja' ) . '</p>',
					'include' => plugin_dir_path( __FILE__ ) . 'views/sites.php',
				),
			),
		),
	] );
}
