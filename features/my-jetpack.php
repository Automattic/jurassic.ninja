<?php
/**
 * WP Debug Log.
 *
 * @package jurassic-ninja
 */

namespace jn;

add_action(
	'jurassic_ninja_init',
	function () {
		$defaults = array(
			'my-jetpack' => false,
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app = null, $features, $domain ) use ( $defaults ) {
				$features = array_merge( $defaults, $features );
				if ( $features['my-jetpack'] ) {
					debug( '%s: Setting JETPACK_ENABLE_MY_JETPACK constant to true', $domain );
					set_my_jetpack();
				}
			},
			1,
			3
		);

		add_filter(
			'jurassic_ninja_rest_feature_defaults',
			function ( $defaults ) {
				return array_merge(
					$defaults,
					array(
						'my-jetpack' => false,
					)
				);
			}
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {
				if ( isset( $json_params['my-jetpack'] ) ) {
					$features['my-jetpack'] = $json_params['my-jetpack'];
				}
				return $features;
			},
			10,
			2
		);
	}
);

/**
 * Sets the WP_DEBUG constants.
 */
function set_my_jetpack() {
	$cmd = 'wp config --type=constant set JETPACK_ENABLE_MY_JETPACK true'
		. ' && wp config --type=constant set JETPACK_ENABLE_MY_JETPACK true';
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

