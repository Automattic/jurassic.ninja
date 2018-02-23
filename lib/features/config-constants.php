<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	add_filter( 'jurassic_ninja_features', function( $features ) {
		return array_merge( $features, [
			'config-constants' => false,
		] );
	} );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) {
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
