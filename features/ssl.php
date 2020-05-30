<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'auto_ssl' => false,
		'ssl' => false,
	];

	add_action( 'jurassic_ninja_add_features_after_create_app', function( &$app, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		// Currently not used but the code works.
		if ( $features['auto_ssl'] ) {
			provisioner()->enable_auto_ssl( $app->id );
		}
		// We can't easily enable SSL for subodmains because
		// wildcard certificates don't support multiple levels of subdomains
		// and this can result in awful experience.
		// Need to explorer a little bit better
		if ( $features['ssl'] && ! ( isset( $features['subdomain_multisite'] ) && $features['subdomain_multisite'] ) ) {
			if ( $features['auto_ssl'] ) {
				debug( 'Both ssl and auto_ssl features were requested. Ignoring ssl and launching with auto_ssl' );
			} else {
				debug( '%s: Enabling custom SSL', $domain );
				$response = provisioner()->enable_ssl( $app->id );
				if ( is_wp_error( $response ) ) {
					debug( 'Error enabling SSL for %s. Check the next log line for a dump of the WP_Error', $domain );
					// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
					debug( print_r( $response, true ) );
					// phpcs:enable
					throw new \Exception( 'Error enabling SSL: ' . $response->get_error_message() );
				}
				add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app = null, $features, $domain ) use ( $defaults ) {
					$features = array_merge( $defaults, $features );
					if ( $features['ssl'] ) {
						debug( '%s: Setting home and siteurl options', $domain );
						set_home_and_site_url( $domain );
					}
				}, 10, 3 );
			}
		}
	}, 10, 3 );
	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'ssl' => (bool) settings( 'ssl_use_custom_certificate', false ),
		] );
	} );

	add_filter( 'jurassic_ninja_created_site_url', function( $domain, $features ) {
		// See note in launch_wordpress() about why we can't launch subdomain_multisite with ssl.
		$schema = ( $features['ssl'] && ! $features['subdomain_multisite'] ) ? 'https' : 'http';
		$url = "$schema://" . $domain;
		return $url;
	}, 10, 2 );
} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page', function( $options_page ) {
		$settings = [
			'title' => __( 'SSL Configuration', 'jurassic-ninja' ),
			'text' => '<p>' . __( 'Paste a wildcard SSL certificate and the private key used to generate it.', 'jurassic-ninja' ) . '</p>',
			'fields' => array(
				'ssl_use_custom_certificate' => array(
					'id' => 'ssl_use_custom_certificate',
					'title' => __( 'Use custom SSL certificate', 'jurassic-ninja' ),
					'type' => 'checkbox',
					'checked' => false,
				),
				'ssl_certificate' => array(
					'id' => 'ssl_certificate',
					'title' => __( 'SSL certificate', 'jurassic-ninja' ),
					'text' => __( 'Paste the text here.', 'jurassic-ninja' ),
					'type' => 'textarea',
				),
				'ssl_private_key' => array(
					'id' => 'ssl_private_key',
					'title' => __( 'The private key used to create the certificate', 'jurassic-ninja' ),
					'text' => __( 'Paste the text here.', 'jurassic-ninja' ),
					'type' => 'textarea',
				),
				'ssl_ca_certificates' => array(
					'id' => 'ssl_ca_certificates',
					'title' => __( 'CA certificates', 'jurassic-ninja' ),
					'text' => __( 'Paste the text here.', 'jurassic-ninja' ),
					'type' => 'textarea',
				),
			),
		];
		$options_page[ SETTINGS_KEY ]['sections']['ssl'] = $settings;
		return $options_page;
	} );
} );

function set_home_and_site_url( $domain ) {
	$cmd = "wp option set siteurl https://$domain"
		. " && wp option set home https://$domain";

	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
