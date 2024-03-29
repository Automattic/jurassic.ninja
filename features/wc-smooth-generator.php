<?php
/**
 * WC Smooth.
 *
 * @package jurassic-ninja
 */

namespace jn;

define( 'WC_SMOOTH_GENERATOR_PLUGIN_URL', 'https://github.com/woocommerce/wc-smooth-generator/releases/latest/download/wc-smooth-generator.zip' );

add_action(
	'jurassic_ninja_init',
	function () {
		$defaults = array(
			'wc-smooth-generator' => false,
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app = null, $features, $domain ) use ( $defaults ) {
				$features = array_merge( $defaults, $features );
				if ( $features['wc-smooth-generator'] ) {
					debug( '%s: Adding WooCommerce Smooth Generator Plugin', $domain );
					add_wc_smooth_generator_plugin();
				}
			},
			10,
			3
		);

		add_filter(
			'jurassic_ninja_rest_feature_defaults',
			function ( $defaults ) {
				return array_merge(
					$defaults,
					array(
						'wc-smooth-generator' => false,
					)
				);
			}
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {
				if ( isset( $json_params['wc-smooth-generator'] ) ) {
					$features['wc-smooth-generator'] = $json_params['wc-smooth-generator'];
					// The WooCommerce Smooth Generator Plugin is meant to work alongside
					// WooCommerce and active.
					if ( $features['wc-smooth-generator'] ) {
						$features['woocommerce'] = true;
					}
				}
				return $features;
			},
			10,
			2
		);

	}
);

/**
 * Installs and activates WooCommerce Smooth Generator plugin on the site.
 */
function add_wc_smooth_generator_plugin() {
	$wc_smooth_generator_plugin_url = WC_SMOOTH_GENERATOR_PLUGIN_URL;
	/**
	 * We install the latest released version of the plugin
	 */
	$cmd = "wp plugin install $wc_smooth_generator_plugin_url --activate";

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}
