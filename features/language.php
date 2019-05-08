<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'language' => false,
	];

	add_action( 'jurassic_ninja_add_features_after_auto_login', function( &$app = null, $features, $domain ) use ( $defaults ) {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$language_list = wp_get_available_translations();
		$codes = array_keys( $language_list );
		$features = array_merge( $defaults, $features );
		if ( $features['language'] && in_array( $features['language'], $codes, true ) ) {
			debug( '%s: Setting language to %s', $domain, $features['language'] );
			set_language( $features['language'] );
		}
	}, 10, 3 );

	add_action( 'jurassic_ninja_enqueue_scripts', function() {
		if ( page_is_specialops() ) {
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			$language_list = wp_get_available_translations();
			wp_localize_script( 'jurassicninja.js', 'availableLanguages', $language_list );
		}
	} );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['language'] ) ) {
			$features['language'] = $json_params['language'];
		}
		return $features;
	}, 10, 2 );
} );

function set_language( $code ) {
	$cmd = "wp language core install $code"
		. " && wp language core activate $code"
		. " && wp language plugin install jetpack $code";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
