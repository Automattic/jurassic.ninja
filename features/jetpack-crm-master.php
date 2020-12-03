<?php

namespace jn;

define( 'JETPACK_CRM_PLUGIN_MASTER_URL', 'https://github.com/automattic/zero-bs-crm/archive/master.zip' );

add_action(
	'jurassic_ninja_init',
	function () {
		$defaults = array(
			'jetpack-crm-master' => false,
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app, $features, $domain ) use ( $defaults ) {
				$features = array_merge( $defaults, $features );
				if ( $features['jetpack-crm-master'] ) {
					// Abort the installation from master if the public plugin is selected
					if ( $features['zero-bs-crm'] ) {
						return;
					}
					debug( '%s: Adding Jetpack CRM Plugin (master branch)', $domain );
					add_jetpack_crm_master_plugin();
				}
			},
			10,
			3
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {
				if ( isset( $json_params['jetpack-crm-master'] ) ) {
					$features['jetpack-crm-master'] = $json_params['jetpack-crm-master'];
				}

				return $features;
			},
			10,
			2
		);
	}
);

/**
 * Installs and activates Jetpack CRM plugin (master branch) on the site.
 */
function add_jetpack_crm_master_plugin() {
	$jetpack_crm_plugin_master_url = JETPACK_CRM_PLUGIN_MASTER_URL;
	$cmd                           = "wp plugin install $jetpack_crm_plugin_master_url --activate";
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}
