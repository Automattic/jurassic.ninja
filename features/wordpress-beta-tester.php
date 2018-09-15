<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'wordpress-beta-tester' => false,
	];

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( isset( $json_params['wordpress-beta-tester'] ) ) {
			$features['wordpress-beta-tester'] = $json_params['wordpress-beta-tester'];
		}
		return $features;
	}, 10, 2 );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['wordpress-beta-tester'] ) {
			debug( '%s: Adding WordPress Beta Tester Plugin', $domain );
			add_wordpress_beta_tester_plugin();
		}
	}, 10, 3 );
} );

/**
 * Installs and activates WordPress Beta Tester plugin on the site.
 */
function add_wordpress_beta_tester_plugin() {
	$cmd = 'wp plugin install wordpress-beta-tester --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
