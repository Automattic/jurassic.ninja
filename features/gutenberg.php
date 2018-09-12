<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'gutenberg' => false,
	];
	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'gutenberg' => (bool) settings( 'add_gutenberg_by_default', false ),
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['gutenberg'] ) ) {
			$features['gutenberg'] = $json_params['gutenberg'];
		}
		return $features;
	}, 10, 2 );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['gutenberg'] ) {
			debug( '%s: Adding Gutenberg', $domain );
			add_gutenberg_plugin();
		}
	}, 10, 3 );

} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page_default_plugins', function( $fields ) {
		$field = [
			'add_gutenberg_by_default' => [
				'id' => 'add_gutenberg_by_default',
				'title' => __( 'Add Gutenberg to every launched WordPress', 'jurassic-ninja' ),
				'text' => __( 'Install and activate Gutenberg on launch', 'jurassic-ninja' ),
				'type' => 'checkbox',
				'checked' => false,
			],
		];
		return array_merge( $fields, $field );
	}, 10 );
} );
/**
 * Installs and activates Gutenberg Plugin on the site.
 */
function add_gutenberg_plugin() {
	$cmd = 'wp plugin install gutenberg --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
