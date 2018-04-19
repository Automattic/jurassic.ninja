<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'jetpack-beta' => false,
		'branch' => false,
	];

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['jetpack-beta'] ) {
			debug( '%s: Adding Jetpack Beta Tester Plugin', $domain );
			add_jetpack_beta_plugin();
		}

		if ( $features['branch'] ) {
			debug( '%s: Activating Jetpack %s branch in Beta plugin', $domain, $features['branch'] );
			activate_jetpack_branch( $features['branch'] );
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'jetpack-beta' => (bool) settings( 'add_jetpack_beta_by_default', false ),
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		$branch = isset( $json_params['branch'] ) ? $json_params['branch'] : 'master';
		if ( isset( $json_params['jetpack-beta'] ) && $json_params['jetpack-beta'] ) {
			$url = get_jetpack_beta_url( $branch );

			$error = null;
			if ( null === $url ) {
				$error = new \WP_Error(
					'failed_to_launch_site_with_branch',
					esc_html__( 'Invalid branch name or not ready yet: ' . $branch ),
					[
						'status' => 400,
					]
				);
			}
			$features['jetpack-beta'] = $error === null ? $json_params['jetpack-beta'] : $error ;
			$features['branch'] = $branch;
		}

		return $features;
	}, 10, 2 );
} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page_default_plugins', function( $fields ) {
		$field = [
			'add_jetpack_beta_by_default' => [
				'id' => 'add_jetpack_beta_by_default',
				'title' => __( 'Add Jetpack Beta Tester plugin to every launched WordPress', 'jurassic-ninja' ),
				'text' => __( 'Install and activate Jetpack Beta Tester on launch', 'jurassic-ninja' ),
				'type' => 'checkbox',
				'checked' => false,
			],
		];
		return array_merge( $fields, $field );
	}, 10 );
} );

/**
 * Installs and activates Jetpack Beta Tester plugin on the site.
 */
function add_jetpack_beta_plugin() {
	$jetpack_beta_plugin_url = JETPACK_BETA_PLUGIN_URL;
	$cmd = "wp plugin install $jetpack_beta_plugin_url --activate" ;
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Activates jetpack branch in Beta plugin
 */
function activate_jetpack_branch( $branch_name ) {
	$cmd = "wp jetpack-beta branch activate $branch_name";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

function get_jetpack_beta_url( $branch_name ) {
	$branch_name = str_replace( '/', '_', $branch_name );
	$manifest_url = 'https://betadownload.jetpack.me/jetpack-branches.json';
	$manifest = json_decode( wp_remote_retrieve_body( wp_remote_get( $manifest_url ) ) );

	if ( ( 'rc' === $branch_name || 'master' === $branch_name ) && isset( $manifest->{$branch_name}->download_url ) ) {
		return $manifest->{$branch_name}->download_url;
	}

	if ( isset( $manifest->pr->{$branch_name}->download_url ) ) {
		return $manifest->pr->{$branch_name}->download_url;
	}
}
