<?php
/**
 * Installer for the client-example plugin.
 *
 * @package jurassic-ninja
 */

namespace jn;

define( 'CLIENT_EXAMPLE_PLUGIN_MASTER_URL', 'https://github.com/Automattic/client-example/archive/master.zip' );

add_action(
	'jurassic_ninja_init',
	function () {
		$defaults = array(
			'client-example' => false,
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app, $features, $domain ) use ( $defaults ) {
				$features = array_merge( $defaults, $features );
				if ( $features['client-example'] ) {
					debug( '%s: Adding Client Example Plugin (master branch)', $domain );
					add_client_example_master_plugin();
				}
			},
			10,
			3
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {
				if ( isset( $json_params['client-example'] ) ) {
					$features['client-example'] = $json_params['client-example'];
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
function add_client_example_master_plugin() {
	$client_example_plugin_master_url = CLIENT_EXAMPLE_PLUGIN_MASTER_URL;

	/**
	 * We install the plugin but don't activate until dependencies are there or it will fail
	 */
	$cmd = "wp plugin install $client_example_plugin_master_url"
		. ' && pushd . && cd wp-content/plugins/client-example'
		. ' && composer install --no-dev'
		. ' && wp plugin activate client-example'
		. ' && popd';

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}
