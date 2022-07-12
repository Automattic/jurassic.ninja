<?php
/**
 * Jetpack Beta Blocks.
 *
 * @package jurassic-ninja
 */

namespace jn;

add_action(
	'jurassic_ninja_init',
	function () {
		$defaults = array(
			'jetpack-beta-blocks' => false,
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app = null, $features, $domain ) use ( $defaults ) {
				$features = array_merge( $defaults, $features );
				if ( $features['jetpack-beta-blocks'] ) {
					debug( '%s: Setting JETPACK_BETA_BLOCKS to true', $domain );
					set_jetpack_beta_blocks();
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
						'jetpack-beta-blocks' => false,
					)
				);
			}
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {
				if ( isset( $json_params['jetpack-beta-blocks'] ) ) {
					$features['jetpack-beta-blocks'] = $json_params['jetpack-beta-blocks'];
				}
				return $features;
			},
			10,
			2
		);
	}
);

/**
 * Sets the JETPACK_BETA_BLOCKS constant.
 */
function set_jetpack_beta_blocks() {
	$cmd = 'wp config --type=constant set JETPACK_BETA_BLOCKS true';
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

