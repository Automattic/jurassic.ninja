<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	add_filter( 'jurassic_ninja_features', function( $features ) {
		return array_merge( $features, [
			'woocommerce' => false,
		] );
	} );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) {
		if ( $features['woocommerce'] ) {
			debug( '%s: Adding WooCommerce', $domain );
			add_woocommerce_plugin();
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'woocommerce' => (bool) settings( 'add_woocommerce_by_default', false ),
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['woocommerce'] ) ) {
			$features['woocommerce'] = $json_params['woocommerce'];
		}
		return $features;
	}, 10, 2 );
} );

add_action( 'jurassic_ninja_admin_init', function( $fields ) {
	add_filter( 'jurassic_ninja_settings_options_page_default_plugins', function( $fields ) {
		$field = [
			'add_woocommerce_by_default' => [
				'id' => 'add_woocommerce_by_default',
				'title' => __( 'Add WooCommerce to every launched WordPress', 'jurassic-ninja' ),
				'text' => __( 'Install and activate WooCommerce on launch', 'jurassic-ninja' ),
				'type' => 'checkbox',
				'checked' => false,
			],
		];
		return array_merge( $fields, $field );
	}, 10 );
} );

/**
 * Installs and activates WooCommerce on the site.
 */
function add_woocommerce_plugin() {
	$cmd = 'wp plugin install woocommerce --activate' ;
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
