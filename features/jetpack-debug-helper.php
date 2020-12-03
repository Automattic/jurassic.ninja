<?php

namespace jn;

define( 'JETPACK_DEBUG_HELPER_PLUGIN_MASTER_URL', 'https://github.com/automattic/jetpack-debug-helper/archive/master.zip' );

add_action(
	'jurassic_ninja_init',
	function () {
		$defaults = array(
			'jetpack-debug-helper' => false,
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app, $features, $domain ) use ( $defaults ) {
				$features = array_merge( $defaults, $features );
				if ( $features['jetpack-debug-helper'] ) {
					debug( '%s: Adding Jetpack Debug Helper Plugin (master branch)', $domain );
					add_jetpack_debug_helper_master_plugin();
				}
			},
			10,
			3
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {
				if ( isset( $json_params['jetpack-debug-helper'] ) ) {
					$features['jetpack-debug-helper'] = $json_params['jetpack-debug-helper'];
				}

				return $features;
			},
			10,
			2
		);
	}
);

/**
 * Installs and activates Jetpack Debug plugin (master branch) on the site.
 */
function add_jetpack_debug_helper_master_plugin() {
	$jetpack_debug_helper_plugin_master_url = JETPACK_DEBUG_HELPER_PLUGIN_MASTER_URL;
	$cmd                           = "wp plugin install $jetpack_debug_helper_plugin_master_url --activate";
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}
