<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'config-constants' => false,
	];

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( isset( $json_params['config-constants'] ) ) {
			$features['config-constants'] = $json_params['config-constants'];
		}
		return $features;
	}, 10, 2 );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['config-constants'] ) {
			debug( '%s: Adding Config Constants Plugin', $domain );
			add_config_constants_plugin();
		}
	}, 10, 3 );

} );

/**
 * Installs and activates the Config Constants plugin on the site.
 */
function add_config_constants_plugin() {
	$cmd = 'wp plugin install config-constants --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
