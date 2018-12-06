<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'wordpress-5-beta' => false,
	];

	add_action( 'jurassic_ninja_install_features_before_companion', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['wordpress-5-beta'] ) {
			debug( '%s: Updating core to latest WordPress 5 beta release', $domain );
			update_to_wordpress_5_beta_latest();
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_feature_defaults_for_rest_api_request', function( $defaults ) {
		return array_merge( $defaults, [
			'wordpress-5-beta' => false,
		] );
	} );

	add_filter( 'jurassic_ninja_features_from_rest_api_request', function( $features, $json_params ) {
		if ( isset( $json_params['wordpress-5-beta'] ) ) {
			$features['wordpress-5-beta'] = $json_params['wordpress-5-beta'];
			// Disable launching with Gutenberg if WordPress 5.0 is requested
			// Just in case they collide at some point
			if ( isset( $json_params['gutenberg'] ) ) {
				$features['gutenberg'] = false;
			}
		}
		return $features;
	}, 11, 2 );

} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page_default_plugins', function( $fields ) {
		$field = [
			'wordpress_5_beta_latest' => [
				'id' => 'wordpress_5_beta_latest',
				'title' => __( 'Latest beta tag for WordPress 5.0', 'jurassic-ninja' ),
				'text' => __( 'Which version to run when wordpress-5-beta requested', 'jurassic-ninja' ),
				'placeholder' => '5.0-beta1',
				'value' => '5.0-beta1',
				'checked' => false,
			],
		];
		return array_merge( $fields, $field );
	}, 10 );
} );

/**
 * Updates WordPress to latest Beta available for 5.0.
 */
function update_to_wordpress_5_beta_latest() {
	$wordpress_5_beta_latest = settings( 'wordpress_5_beta_latest', '5.0-beta1' );
	$cmd = "wp core update --version=$wordpress_5_beta_latest && wp core update-db";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
