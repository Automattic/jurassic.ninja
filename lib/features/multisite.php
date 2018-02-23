<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	add_filter( 'jurassic_ninja_features', function( $features ) {
		return array_merge( $features, [
			'subdir_multisite' => false,
			'subdomain_multisite' => false,
		] );
	} );

	add_action( 'jurassic_ninja_do_feature_conditions', function( $features ) {
		if ( $features['subdir_multisite'] && $features['subdomain_multisite'] ) {
			throw new \Exception( 'not-both-multisite-types', __( "Don't try to enable both types of multisite" ) );
		}
	} );

	add_action( 'jurassic_ninja_add_features_after_auto_login', function( &$app, $features, $domain ) {
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

	add_filter( 'create_endpoint_feature_defaults', function( $defaults ) {
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

