<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'wp-log-viewer' => false,
	];

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( isset( $json_params['wp-log-viewer'] ) ) {
			$features['wp-log-viewer'] = $json_params['wp-log-viewer'];
		}
		return $features;
	}, 10, 2 );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) use ( $defaults ) {
		if ( $features['wp-log-viewer'] ) {
			debug( '%s: Adding WP Log Viewer Plugin', $domain );
			add_wp_log_viewer_plugin();
		}
	}, 10, 3 );

} );


/**
 * Installs and activates the WP Log Viewer plugin on the site.
 */
function add_wp_log_viewer_plugin() {
	$cmd = 'wp plugin install wp-log-viewer --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
