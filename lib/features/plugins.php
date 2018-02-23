<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	add_filter( 'jurassic_ninja_features', function( $features ) {
		return array_merge( $features, [
			'jetpack' => false,
			'branch' => false,
		] );
	} );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) {
		if ( $features['jetpack'] ) {
			debug( '%s: Adding Jetpack', $domain );
			add_jetpack();
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_create_endpoint_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'jetpack' => (bool) settings( 'add_jetpack_by_default', true ),
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['jetpack'] ) ) {
			$features['jetpack'] = $json_params['jetpack'];
		}
		return $features;
	}, 10, 2 );
} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page', function( $options_page ) {
		$fields = apply_filters( 'jurassic_ninja_settings_options_page_default_plugins', [
			'add_jetpack_by_default' => [
				'id' => 'add_jetpack_by_default',
				'title' => __( 'Add Jetpack to every launched WordPress', 'jurassic-ninja' ),
				'text' => __( 'Install and activate Jetpack on launch', 'jurassic-ninja' ),
				'type' => 'checkbox',
				'checked' => true,
			],
		] );
		$settings = [
			'title' => __( 'Default plugins', 'jurassic-ninja' ),
			'text' => '<p>' . __( 'Choose plugins you want installed on launch by default.' ) . '</p>',
			'fields' => $fields,
		];

		$options_page[ SETTINGS_KEY ]['sections']['plugins'] = $settings;
		return $options_page;
	}, 1 );
}, 1 );

/**
 * Installs and activates Jetpack on the site.
 */
function add_jetpack() {
	$cmd = 'wp plugin install jetpack --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
