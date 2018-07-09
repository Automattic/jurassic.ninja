<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'code-snippets' => false,
	];
	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'code-snippets' => false,
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['code-snippets'] ) ) {
			$features['code-snippets'] = $json_params['code-snippets'];
		}
		return $features;
	}, 10, 2 );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['code-snippets'] ) {
			debug( '%s: Adding Code Snippets', $domain );
			add_code_snippets_plugin();
		}
	}, 10, 3 );

} );

/**
 * Installs and activates Code Snippets Plugin on the site.
 */
function add_code_snippets_plugin() {
	$cmd = 'wp plugin install code-snippets --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
