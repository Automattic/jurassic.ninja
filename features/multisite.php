<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'subdir_multisite' => false,
		'subdomain_multisite' => false,
	];

	add_action( 'jurassic_ninja_do_feature_conditions', function( $features ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['subdir_multisite'] && $features['subdomain_multisite'] ) {
			throw new \Exception( __( "Don't try to enable both types of multisite", 'jurassic-ninja' ) );
		}
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		// Check we're not asked for both multisite types
		$error = null;
		if (
			isset( $json_params['subdir_multisite'] ) && $json_params['subdir_multisite'] &&
			isset( $json_params['subdomain_multisite'] ) && $json_params['subdomain_multisite']
		) {
				$error = new \WP_Error(
					'failed_to_launch_site_with_both_multisite_types',
					/* translators: is a GitHub branch name */
					esc_html__( 'You cannot request both types of multisite', 'jurassic-ninja' ),
					[
						'status' => 400,
					]
				);
		}

		if ( isset( $json_params['subdir_multisite'] ) ) {
			$features['subdir_multisite'] = null === $error ? $json_params['subdir_multisite'] : $error;
		}
		if ( isset( $json_params['subdomain_multisite'] ) ) {
			$features['subdomain_multisite'] = null === $error ? $json_params['subdomain_multisite'] : $error;
		}
		return $features;
	}, 10, 2 );

	add_action( 'jurassic_ninja_add_features_after_auto_login', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		// Enabling multisite is done very late so we can install plugins first without
		// having to decide if network activate them afterwards.
		if ( $features['subdir_multisite'] ) {
			debug( '%s: Enabling subdir based multisite', $domain );
			enable_subdir_multisite( $domain );
		}

		if ( $features['subdomain_multisite'] ) {
			debug( '%s: Enabling subdomain based multisite', $domain );
			enable_subdomain_multisite( $domain );
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'subdomain_multisite' => false,
		] );
	} );
} );

/**
 * Enables subdir-based multisite on a WordPress instance
 * @param string  $domain          The main domain for the site
 * @return [type]                   [description]
 */
function enable_subdir_multisite( $domain ) {
	$file_url = SUBDIR_MULTISITE_HTACCESS_TEMPLATE_URL;
	$email = settings( 'default_admin_email_address' );
	$cmd = "wp core multisite-install --title=\"subdir-based Network\" --url=\"$domain\" --admin_email=\"$email\" --skip-email"
		. " && cp .htaccess .htaccess-not-multisite && wget '$file_url' -O .htaccess";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Enables subdomain-based multisite on a WordPress instance
 * @param string  $domain          The main domain for the site.
 * @return [type]                   [description]
 */
function enable_subdomain_multisite( $domain ) {
	$file_url = SUBDOMAIN_MULTISITE_HTACCESS_TEMPLATE_URL;
	$email = settings( 'default_admin_email_address' );
	// For some reason, the option auto_login gets set to a 0 after enabling multisite-install,
	// like if there were a sort of inside login happening magically.
	$cmd = "wp core multisite-install --title=\"subdomain-based Network\" --url=\"$domain\" --admin_email=\"$email\" --subdomains --skip-email"
		. " && cp .htaccess .htaccess-not-multisite && wget '$file_url' -O .htaccess"
		. ' && wp option update auto_login 1';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

