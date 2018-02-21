<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	add_filter( 'jurassic_ninja_features', function( $features ) {
		return array_merge( $features, [
			'auto_ssl' => false,
			'ssl' => false,
		] );
	} );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) {

		// Currently not used but the code works.
		if ( $features['auto_ssl'] ) {
			enable_sp_auto_ssl( $app->data->id );
		}
		// We can't easily enable SSL for subodmains because
		// wildcard certificates don't support multiple levels of subdomains
		// and this can result in awful experience.
		// Need to explorer a little bit better
		if ( $features['ssl'] && ! $features['subdomain_multisite'] ) {
			if ( $features['auto_ssl'] ) {
				debug( 'Both ssl and auto_ssl features were requested. Ignoring ssl and launching with auto_ssl' );
			} else {
				debug( '%s: Enabling custom SSL', $domain );
				$response = enable_sp_ssl( $app->data->id );
				if ( is_wp_error( $response ) ) {
					debug( 'Error enabling SSL for %s. Check the next log line for a dump of the WP_Error', $domain );
					debug( print_r( $response, true ) );
					throw new \Exception( 'Error creating sysuser: ' . $return->get_error_message() );
				}
			}
		}
	}, 10, 3 );
} );
