<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app = null, $features, $domain ) {
		if (
			false === settings( 'wordpress_5_hack', false )
			// Do not upgrade if wordpress-4 was requested
			|| ( isset( $features['wordpress-4'] ) && $features['wordpress-4'] )
		) {
			return;
		}
		debug( '%s: Updating core to latest WordPress 5 release', $domain );
		update_to_wordpress_5_latest();
	}, 10, 3 );

} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page_default_plugins', function( $fields ) {
		$field = [
			'wordpress_5_hack' => [
				'id' => 'wordpress_5_hack',
				'title' => __( 'Force launching of WordPress 5', 'jurassic-ninja' ),
				'text' => __( 'Until ServerPilot starts installing WordPress 5, we need this hack.', 'jurassic-ninja' ),
				'type' => 'checkbox',
				'checked' => false,
			],
		];
		return array_merge( $fields, $field );
	}, 10 );
} );

/**
 * Updates WordPress to latest stable available for 5.0.
 */
function update_to_wordpress_5_latest() {
	$cmd = 'wp core update && wp core update-db';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
