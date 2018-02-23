<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	add_filter( 'jurassic_ninja_features', function( $features ) {
		return array_merge( $features, [
			'config-constants' => false,
			'gutenberg' => false,
			'jetpack' => false,
			'jetpack-beta' => false,
			'woocommerce' => false,
			'wordpress-beta-tester' => false,
			'wp-log-viewer' => false,
			'branch' => false,
		] );
	} );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app, $features, $domain ) {
		if ( $features['jetpack'] ) {
			debug( '%s: Adding Jetpack', $domain );
			add_jetpack();
		}

		if ( $features['jetpack-beta'] ) {
			debug( '%s: Adding Jetpack Beta Tester Plugin', $domain );
			add_jetpack_beta_plugin();
		}

		if ( $features['branch'] ) {
			debug( '%s: Activating Jetpack %s branch in Beta plugin', $domain, $features['branch'] );
			activate_jetpack_branch( $features['branch'] );
		}

		if ( $features['wordpress-beta-tester'] ) {
			debug( '%s: Adding WordPress Beta Tester Plugin', $domain );
			add_wordpress_beta_tester_plugin();
		}

		if ( $features['config-constants'] ) {
			debug( '%s: Adding Config Constants Plugin', $domain );
			add_config_constants_plugin();
		}

		if ( $features['wp-log-viewer'] ) {
			debug( '%s: Adding WP Log Viewer Plugin', $domain );
			add_wp_log_viewer_plugin();
		}

		if ( $features['gutenberg'] ) {
			debug( '%s: Adding Gutenberg', $domain );
			add_gutenberg_plugin();
		}

		if ( $features['woocommerce'] ) {
			debug( '%s: Adding WooCommerce', $domain );
			add_woocommerce_plugin();
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_create_endpoint_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'jetpack' => (bool) settings( 'add_jetpack_by_default', true ),
			'jetpack-beta' => (bool) settings( 'add_jetpack_beta_by_default', false ),
			'woocommerce' => (bool) settings( 'add_woocommerce_by_default', false ),
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['jetpack'] ) ) {
			$features['jetpack'] = $json_params['jetpack'];
		}
		if ( isset( $json_params['woocommerce'] ) ) {
			$features['woocommerce'] = $json_params['woocommerce'];
		}
		if ( isset( $json_params['jetpack-beta'] ) ) {
			$url = get_jetpack_beta_url( $json_params['branch'] );

			if ( null === $url ) {
				return new \WP_Error(
					'failed_to_launch_site_with_branch',
					esc_html__( 'Invalid branch name or not ready yet: ' . $json_params['branch'] ),
					[
						'status' => 400,
					]
				);
			}
			$features['jetpack-beta'] = $json_params['jetpack-beta'];
			$features['branch'] = $json_params['branch'];
		}

		return $features;
	}, 10, 2 );
} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page', function( $options_page ) {
		$settings = [
			'title' => __( 'Default plugins', 'jurassic-ninja' ),
			'text' => '<p>' . __( 'Choose plugins you want installed on launch by default.' ) . '</p>',
			'fields' => array(
				'add_jetpack_by_default' => array(
					'id' => 'add_jetpack_by_default',
					'title' => __( 'Add Jetpack to every launched WordPress', 'jurassic-ninja' ),
					'text' => __( 'Install and activate Jetpack on launch', 'jurassic-ninja' ),
					'type' => 'checkbox',
					'checked' => true,
				),
				'add_jetpack_beta_by_default' => array(
					'id' => 'add_jetpack_beta_by_default',
					'title' => __( 'Add Jetpack Beta Tester plugin to every launched WordPress', 'jurassic-ninja' ),
					'text' => __( 'Install and activate Jetpack Beta Tester on launch', 'jurassic-ninja' ),
					'type' => 'checkbox',
					'checked' => false,
				),
				'add_woocommerce_by_default' => array(
					'id' => 'add_woocommerce_by_default',
					'title' => __( 'Add WooCommerce to every launched WordPress', 'jurassic-ninja' ),
					'text' => __( 'Install and activate WooCommerce on launch', 'jurassic-ninja' ),
					'type' => 'checkbox',
					'checked' => false,
				),
			),
		];
		$options_page[ SETTINGS_KEY ]['sections']['plugins'] = $settings;
		return $options_page;
	}, 1);
} );

/**
 * Installs and activates the Config Constants plugin on the site.
 */
function add_config_constants_plugin() {
	$cmd = 'wp plugin install config-constants --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates Gutenberg Plugin on the site.
 */
function add_gutenberg_plugin() {
	$cmd = 'wp plugin install gutenberg --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates Jetpack on the site.
 */
function add_jetpack() {
	$cmd = 'wp plugin install jetpack --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

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

/**
 * Installs and activates WooCommerce on the site.
 */
function add_woocommerce_plugin() {
	$cmd = 'wp plugin install woocommerce --activate' ;
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates WordPress Beta Tester plugin on the site.
 */
function add_wordpress_beta_tester_plugin() {
	$cmd = 'wp plugin install wordpress-beta-tester --activate' ;
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates the WP Log Viewer plugin on the site.
 */
function add_wp_log_viewer_plugin() {
	$cmd = 'wp plugin install wp-log-viewer --activate';
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
