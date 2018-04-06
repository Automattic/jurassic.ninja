<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'wp-rollback' => false,
	];

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['wp-rollback'] ) {
			debug( '%s: Adding WP Rollback', $domain );
			add_wp_rollback_plugin();
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'wp-rollback' => false,
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['wp-rollback'] ) ) {
			$features['wp-rollback'] = $json_params['wp-rollback'];
		}
		return $features;
	}, 10, 2 );
} );

/**
 * Installs and activates WP Rollback on the site.
 */
function add_wp_rollback_plugin() {
	$cmd = 'wp plugin install wp-rollback --activate' ;
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
