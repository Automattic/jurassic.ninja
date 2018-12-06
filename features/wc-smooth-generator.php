<?php

namespace jn;

define( 'WC_SMOOTH_GENERATOR_PLUGIN_URL', 'https://github.com/woocommerce/wc-smooth-generator/archive/master.zip' );

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'wc-smooth-generator' => false,
	];

	add_action( 'jurassic_ninja_install_features_before_companion', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['wc-smooth-generator'] ) {
			debug( '%s: Adding WooCommerce Smooth Generator Plugin', $domain );
			add_wc_smooth_generator_plugin();
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_feature_defaults_for_rest_api_request', function( $defaults ) {
		return array_merge( $defaults, [
			'wc-smooth-generator' => false,
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['wc-smooth-generator'] ) ) {
			$features['wc-smooth-generator'] = $json_params['wc-smooth-generator'];
			// The WooCommerce Smooth Generator Plugin is meant to work alongside
			// WooCommerce and active.
			if ( $features['wc-smooth-generator'] ) {
				$features['woocommerce'] = true;
			}
		}
		return $features;
	}, 10, 2 );

} );

/**
 * Installs and activates WooCommerce Smooth Generator plugin on the site.
 */
function add_wc_smooth_generator_plugin() {
	$wc_smooth_generator_plugin_url = WC_SMOOTH_GENERATOR_PLUGIN_URL;
	/**
	 * We install the plugin but don't activate until dependencies are there or it will fail
	 */
	$cmd = "wp plugin install $wc_smooth_generator_plugin_url"
		. ' && pushd . && cd wp-content/plugins/wc-smooth-generator'
		. ' && composer install --no-dev'
		. ' && wp plugin activate wc-smooth-generator'
		. ' && popd';

	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
