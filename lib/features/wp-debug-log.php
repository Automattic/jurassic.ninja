<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	add_filter( 'jurassic_ninja_features', function( $features ) {
		return array_merge( $features, [
			'wp-debug-log' => false,
		] );
	} );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) {
		if ( $features['wp-debug-log'] ) {
			debug( '%s: Setting WP_DEBUG_LOG and WP_DEBUG_LOG to true', $domain );
			set_wp_debug_log();
		}
	}, 1, 3 );

	add_filter( 'create_endpoint_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'wp-debug-log' => (bool) settings( 'set_wp_debug_log_by_default', false ),
		] );
	} );
} );

function set_wp_debug_log() {
	$cmd = 'wp config --type=constant set WP_DEBUG true'
		. ' && wp config --type=constant set WP_DEBUG_LOG true'
		. ' && touch wp-content/debug.log';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

