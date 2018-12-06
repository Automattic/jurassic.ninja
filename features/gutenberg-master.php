<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'gutenberg-master' => false,
	];

	add_action( 'jurassic_ninja_install_features_before_companion', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['gutenberg-master'] ) {
			debug( '%s: Adding bleeding edge Gutenberg plugin', $domain );
			add_gutenberg_master();
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'gutenberg-master' => false,
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['gutenberg-master'] ) ) {
			$features['gutenberg-master'] = $json_params['gutenberg-master'];
		}
		return $features;
	}, 10, 2 );

} );

/**
 * Installs and activates the bleeding edge version of the Gutenberg plugin from GitHub.
 */
function add_gutenberg_master() {
	$cmd = 'curl https://gist.githubusercontent.com/oskosk/0b7c45522c945a62309dd57103f94133/raw/build-gutenberg-master.sh --output build-gutenberg-master.sh'
		. ' && source build-gutenberg-master.sh'
		. ' && wp plugin activate gutenberg';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
