<?php

namespace jn;

define( 'WOOCOMMERCE_BETA_TESTER_PLUGIN_URL', 'https://github.com/woocommerce/woocommerce-beta-tester/archive/master.zip' );

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'woocommerce-beta-tester' => false,
	];

	add_action( 'jurassic_ninja_install_features_before_companion', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['woocommerce-beta-tester'] ) {
			debug( '%s: Adding WooCommerce Beta Tester Plugin', $domain );
			add_woocommerce_beta_tester_plugin();
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'woocommerce-beta-tester' => (bool) settings( 'add_woocommerce_beta_tester_by_default', false ),
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['woocommerce-beta-tester'] ) ) {
			$features['woocommerce-beta-tester'] = $json_params['woocommerce-beta-tester'];
			// The WooCommerce Beta Tester Plugin works only when woocommerce is installed and active too
			if ( $features['woocommerce-beta-tester'] ) {
				$features['woocommerce'] = true;
			}
		}
		return $features;
	}, 10, 2 );

} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page_default_plugins', function( $fields ) {
		$field = [
			'add_woocommerce_beta_tester_by_default' => [
				'id' => 'add_woocommerce_beta_tester_by_default',
				'title' => __( 'Add WooCommerce Beta Tester plugin to every launched WordPress', 'jurassic-ninja' ),
				'text' => __( 'Install and activate WooCommerce Beta Tester on launch', 'jurassic-ninja' ),
				'type' => 'checkbox',
				'checked' => false,
			],
		];
		return array_merge( $fields, $field );
	}, 10 );
} );

/**
 * Installs and activates WooCommerce Beta Tester plugin on the site.
 */
function add_woocommerce_beta_tester_plugin() {
	$woocommerce_beta_tester_plugin_url = WOOCOMMERCE_BETA_TESTER_PLUGIN_URL;
	$cmd = "wp plugin install $woocommerce_beta_tester_plugin_url --activate";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
