<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'gutenberg-theme' => false,
		'gutenberg-theme-branch' => false,
	];

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['gutenberg-theme'] ) {
			debug( '%s: Adding Gutenberg Theme %s', $domain, $features['gutenberg-theme'] );
			add_gutenberg_them_from_github( $theme );
		}

		if ( $features['gutenberg-theme-branch'] ) {
			debug( '%s: Activating %s branch in the theme', $domain, $features['gutenberg-theme-branch'] );
			activate_theme_branch( $features['gutenberg-theme-branch'] );
		}
	}, 10, 3 );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		$branch = isset( $json_params['branch'] ) ? $json_params['branch'] : 'master';
		if ( isset( $json_params['gutenberg-theme'] ) && $json_params['gutenberg-theme'] ) {
			$url = get_gutenberg_theme_url( $branch );

			$error = null;
			if ( null === $url ) {
				$error = new \WP_Error(
					'failed_to_launch_site_with_branch',
					/* translators: is a GitHub branch name */
					sprintf( esc_html__( 'Invalid branch name or not ready yet: %s', 'jurassic-ninja' ), $branch ),
					[
						'status' => 400,
					]
				);
			}
			$features['gutenberg-theme'] = null === $error ? $json_params['gutenberg-theme'] : $error;
			$features['branch'] = $branch;
		}

		return $features;
	}, 10, 2 );
} );

/**
 * Installs and activates Jetpack Beta Tester plugin on the site.
 */
function add_gutenberg_them_from_github( $theme ) {
	$theme_url = '';
	$cmd = "wp theme install $theme_url --activate";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Activates jetpack branch in Beta plugin
 */
function activate_theme_branch( $branch_name ) {
	$cmd = "wp gutenberg-theme branch activate $branch_name";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

function get_gutenberg_theme_url( $theme, $branch_name = 'master' ) {
	$available_gutenberg_themes = get_gutenberg_thems();
	if ( isset( $available_gutenberg_themes[ $theme ] ) ) {
		$url = $available_gutenberg_themes[ $theme ];
		$url .= "$available_gutenberg_themes/archive/$branch_name.zip";
		return $url;
	}
	throw new Exception();
}

function get_gutenberg_thems() {
	$available_gutenberg_themes = [
		'blogging' => 'https://github.com/Automattic/default-blogging-theme-dev/',
		'business' => 'https://github.com/Automattic/default-small-business-theme/',
	];
	return $available_gutenberg_themes;
}
