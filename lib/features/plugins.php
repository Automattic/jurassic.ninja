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
