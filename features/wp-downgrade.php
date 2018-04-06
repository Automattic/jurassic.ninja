<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'wp-downgrade' => false,
	];

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['wp-downgrade'] ) {
			debug( '%s: Adding WP Downgrade', $domain );
			add_wp_downgrade_plugin();
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'wp-downgrade' => false,
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['wp-downgrade'] ) ) {
			$features['wp-downgrade'] = $json_params['wp-downgrade'];
		}
		return $features;
	}, 10, 2 );
} );

/**
 * Installs and activates WP Downgrade on the site.
 */
function add_wp_downgrade_plugin() {
	$cmd = 'wp plugin install wp-downgrade --activate' ;
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
