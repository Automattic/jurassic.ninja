<?php

namespace jn;

/**
 *
 * This feature allows installation of a few whitelisted plugins that are available on
 * the Plugin Directory.
 *
 */

add_action( 'jurassic_ninja_init', function() {
	$whitelist = [
		'amp'                   => 'AMP',
		'zero-bs-crm'           => 'Jetpack CRM',
		'classic-editor'        => 'Classic Editor',
		'code-snippets'         => 'Code Snippets',
		'config-constants'      => 'Config Constants',
		'crowdsignal'           => 'Crowdsignal',
		'gutenberg'             => 'Gutenberg',
		'jetpack'               => 'Jetpack',
		'vaultpress'            => 'VaultPress',
		'woocommerce'           => 'WooCommerce',
		'wordpress-beta-tester' => 'WordPress Beta Tester Plugin',
		'wp-downgrade'          => 'WP Downgrade',
		'wp-job-manager'        => 'WP Job Manager',
		'wp-log-viewer'         => 'WP Log Viewer',
		'wp-rollback'           => 'WP Rollback',
		'wp-super-cache'        => 'WP Super Cache',
	];
	// Set all defaults to false.
	// Will probably add a filter here.
	$defaults = array_map( function( $slug ) {
		return false;
	}, $whitelist );

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app = null, $features, $domain ) use ( $defaults, $whitelist ) {
		$features = array_merge( $defaults, $features );
		foreach ( $whitelist as $slug => $name ) {
			// Hack for Crowdsignal cause it's still referred to as polldaddy on the org repo
			if ( 'crowdsignal' === $slug ) {
				if ( isset( $features['crowdsignal'] ) && $features['crowdsignal'] ) {
					debug( '%s: Adding %s', $domain, $name );
					add_directory_plugin( 'polldaddy' );
				}
				continue;
			}
			if ( isset( $features[ $slug ] ) && $features[ $slug ] ) {
				debug( '%s: Adding %s', $domain, $name );
				add_directory_plugin( $slug );
			}
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_rest_feature_defaults', function( $defaults ) {
		return array_merge( $defaults, [
			'gutenberg' => (bool) settings( 'add_gutenberg_by_default', false ),
			'jetpack' => (bool) settings( 'add_jetpack_by_default', true ),
			'woocommerce' => (bool) settings( 'add_woocommerce_by_default', false ),
		] );
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) use ( $whitelist ) {
		foreach ( $whitelist as $slug => $name ) {
			if ( ! isset( $json_params[ $slug ] ) ) {
				continue;
			}
			if ( $json_params[ $slug ] ) {
				$features[ $slug ] = true;
			} else if ( false === $json_params[ $slug ] ) {
				$features[ $slug ] = false;
			}
		}
		return $features;
	}, 10, 2 );
} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page', function( $options_page ) {
		/**
		 * Filter settings about default plugins.
		 *
		 * @since 3.0
		 *
		 * @param array $settings_default_plugins Array of settings entries. See RationalOptionPages docs.
		 */
		$fields = apply_filters( 'jurassic_ninja_settings_options_page_default_plugins', [
			'add_jetpack_by_default' => [
				'id' => 'add_jetpack_by_default',
				'title' => __( 'Add Jetpack to every launched WordPress', 'jurassic-ninja' ),
				'text' => __( 'Install and activate Jetpack on launch', 'jurassic-ninja' ),
				'type' => 'checkbox',
				'checked' => true,
			],
			'jetpack_licensing_api_oauth2_token' => [
				'id' => 'jetpack_licensing_api_oauth2_token',
				'title' => __( 'Jetpack Licensing API OAuth2 Token', 'jurassic-ninja' ),
				'text' => __( 'Licensing API OAuth2 token to provision Jetpack products with. Leave blank to disable.', 'jurassic-ninja' ),
				'sanitize' => false,
			],
			'add_gutenberg_by_default' => [
				'id' => 'add_gutenberg_by_default',
				'title' => __( 'Add Gutenberg to every launched WordPress', 'jurassic-ninja' ),
				'text' => __( 'Install and activate Gutenberg on launch', 'jurassic-ninja' ),
				'type' => 'checkbox',
				'checked' => false,
			],
			'add_woocommerce_by_default' => [
				'id' => 'add_woocommerce_by_default',
				'title' => __( 'Add WooCommerce to every launched WordPress', 'jurassic-ninja' ),
				'text' => __( 'Install and activate WooCommerce on launch', 'jurassic-ninja' ),
				'type' => 'checkbox',
				'checked' => false,
			],
		] );
		$settings = [
			'title' => __( 'Default plugins', 'jurassic-ninja' ),
			'text' => '<p>' . __( 'Choose plugins you want installed on launch by default.', 'jurassic-ninja' ) . '</p>',
			'fields' => $fields,
		];

		$options_page[ SETTINGS_KEY ]['sections']['plugins'] = $settings;
		return $options_page;
	}, 1 );
}, 1 );

/**
 * Installs and activates a given plugin from the Plugin Directory.
 */
function add_directory_plugin( $plugin_slug ) {
	$cmd = "wp plugin install $plugin_slug --activate";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
