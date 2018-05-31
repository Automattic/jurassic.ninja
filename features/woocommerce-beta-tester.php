<?php

namespace jn;

define( 'WOOCOMMERCE_BETA_TESTER_PLUGIN_URL', 'https://github.com/woocommerce/woocommerce-beta-tester/archive/master.zip' );

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'woocommerce-beta-tester' => false,
	];

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['woocommerce-beta-tester'] ) {
			debug( '%s: Adding WooCommerce Beta Tester Plugin', $domain );
			add_woocommerce_beta_tester_plugin();
		}
	}, 10, 3 );
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
