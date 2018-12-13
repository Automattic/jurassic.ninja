<?php
/**
 * This feature provides the ability to launch with latest stabel WordPress 4.
 */
namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'wordpress-4' => false,
	];

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['wordpress-4'] ) {
			debug( '%s: Updating core to latest WordPress 4 latest stable release', $domain );
			update_to_wordpress_4_latest();
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'wordpress-4' => false,
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['wordpress-4'] ) ) {
			$features['wordpress-4'] = $json_params['wordpress-4'];
		}
		return $features;
	}, 11, 2 );

} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page_default_plugins', function( $fields ) {
		$field = [
			'wordpress_4_latest' => [
				'id' => 'wordpress_4_latest',
				'title' => __( 'Latest tag for WordPress 4', 'jurassic-ninja' ),
				'text' => __( 'Which version to run when wordpress-4 requested', 'jurassic-ninja' ),
				'placeholder' => '4.9.8',
				'value' => '4.9.8',
				'checked' => false,
			],
		];
		return array_merge( $fields, $field );
	}, 10 );
} );

/**
 * Updates WordPress to latest tag available for WordPress 4.
 */
function update_to_wordpress_4_latest() {
	$wordpress_4_latest = settings( 'wordpress_4_latest', '4.9.8' );
	// We need --force because this may be a downgrade
	// Force the latest tag defined in settings but attempt to update the minor version just in case
	// another minor is released before settings are updated to deal with the very latest tag for 4.
	$cmd = "wp core update --version=$wordpress_4_latest --force && wp core update --minor";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
