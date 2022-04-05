<?php
/**
 * CRM for everyone.
 *
 * @package jurassic-ninja
 */

namespace jn;

add_action(
	'jurassic_ninja_init',
	function () {

		$defaults = array(
			'jpcrm' => false,
			'jpcrm-build' => false,
			'jpcrm-version' => false,
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app, $features, $domain ) use ( $defaults ) {

				$features = array_merge( $defaults, $features );

				// Don't install if Jetpack CRM isn't specified.
				if ( ! $features['jpcrm'] ) {
					return;
				}

				if ( $features['jpcrm-version'] ) {

				// Install specified version of Jetpack CRM from WP.org repo.
					debug( '%s: Installing Jetpack CRM version %s from WP.org repo', $domain, $features['jpcrm-version'] );
					add_jpcrm_from_wporg( $features['jpcrm-version'] );

				} elseif ( $features['jpcrm-build'] ) {

					// Install custom build of Jetpack CRM.
					debug( '%s: Installing Jetpack CRM from %s', $domain, $features['jpcrm-build'] );
					add_jpcrm_from_custom_build( $features['jpcrm-build'] );

				} else {

					// Install current version of Jetpack CRM from WP.org repo.
					debug( '%s: Installing Jetpack CRM from %s', $domain, $features['jpcrm-build'] );
					add_directory_plugin( 'zero-bs-crm' );

				}

			},
			10,
			3
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {

				if ( isset( $json_params['jpcrm-version'] ) ) {
					$features['jpcrm-version'] = $json_params['jpcrm-version'];
				}

				if ( isset( $json_params['jpcrm-build'] ) ) {
					$features['jpcrm-build'] = $json_params['jpcrm-build'];
				}

				return $features;
			},
			10,
			2
		);
	}
);

/**
 * Installs and activates a specified version of Jetpack CRM from the WP.org plugin repo.
 */
function add_jpcrm_from_wporg( $version ) {

	// Verify we have a valid version number.
	if ( ! version_compare( $version, '1.0.0', '>=' ) ) {
		return new \WP_Error( 'bad_version_number', 'Bad version number.', array( 'status' => 404 ) );
	}

	$cmd = "wp plugin install zero-bs-crm --version=$version --activate";
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);

}

/**
 * Installs and activates a specified build of Jetpack CRM from our custom build URL.
 */
function add_jpcrm_from_custom_build( $build ) {

	// For now, require commit SHA-1 hash (40 char long hex).
	if ( ! preg_match( '/^[A-Fa-f0-9]{40}$/', $build ) ) {
		return new \WP_Error( 'bad_commit_hash', 'Invalid commit hash.', array( 'status' => 404 ) );
	}

	$jpcrm_build_base_url = 'https://TBD/builds/';
	$jpcrm_build_url = $jpcrm_build_base_url . 'zero-bs-crm-' . $build . '.zip';
	$cmd = "wp plugin install $jpcrm_build_url --activate";
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}