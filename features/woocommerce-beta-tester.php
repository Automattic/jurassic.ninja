<?php
/**
 * WC Beta Tester.
 *
 * @package jurassic-ninja
 */

namespace jn;

add_action(
	'jurassic_ninja_added_rest_api_endpoints',
	function () {
		add_get_endpoint(
			'woocommerce-beta-tester/branches',
			function () {
				$manifest_url = 'https://betadownload.jetpack.me/woocommerce-branches.json';
				$manifest = json_decode( wp_remote_retrieve_body( wp_remote_get( $manifest_url ) ) );

				return $manifest;
			}
		);
	}
);

add_action(
	'jurassic_ninja_init',
	function () {
		$defaults = array(
			'woocommerce-beta-tester' => false,
			'woocommerce-beta-tester-live-branch' => false,
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app = null, $features, $domain ) use ( $defaults ) {
				$features = array_merge( $defaults, $features );
				if ( $features['woocommerce-beta-tester'] ) {
					debug( '%s: Adding WooCommerce Beta Tester Plugin', $domain );
					add_woocommerce_beta_tester_plugin();

					if ( $features['woocommerce-beta-tester-live-branch'] ) {
						$branch = $features['woocommerce-beta-tester-live-branch'];

						debug( '%s: Adding WooCommerce Live Branch: %s', $domain, $branch );
						add_woocommerce_live_branch( $branch );
					}
				}
			},
			10,
			3
		);

		add_filter(
			'jurassic_ninja_rest_feature_defaults',
			function ( $defaults ) {
				return array_merge(
					$defaults,
					array(
						'woocommerce-beta-tester' => (bool) settings( 'add_woocommerce_beta_tester_by_default', false ),
					)
				);
			}
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {
				if ( isset( $json_params['woocommerce-beta-tester'] ) ) {
					$features['woocommerce-beta-tester'] = $json_params['woocommerce-beta-tester'];
				}

				if ( isset( $json_params['woocommerce-beta-tester-live-branch'] ) ) {
					$features['woocommerce-beta-tester-live-branch'] = $json_params['woocommerce-beta-tester-live-branch'];
				}

				return $features;
			},
			10,
			2
		);

	}
);

add_action(
	'jurassic_ninja_admin_init',
	function () {
		add_filter(
			'jurassic_ninja_settings_options_page_default_plugins',
			function ( $fields ) {
				$field = array(
					'add_woocommerce_beta_tester_by_default' => array(
						'id' => 'add_woocommerce_beta_tester_by_default',
						'title' => __( 'Add WooCommerce Beta Tester plugin to every launched WordPress', 'jurassic-ninja' ),
						'text' => __( 'Install and activate WooCommerce Beta Tester on launch', 'jurassic-ninja' ),
						'type' => 'checkbox',
						'checked' => false,
					),
				);
				return array_merge( $fields, $field );
			},
			10
		);
	}
);

/**
 * Get the WooCommerce Beta Tester Plugin Zip URL from Github.
 */
function get_woocommerce_beta_tester_zip_url() {
	$url = 'https://api.github.com/repos/woocommerce/woocommerce/releases';
	$response = wp_remote_get( $url );

	if ( is_wp_error( $response ) ) {
			return false;
	} else {
		$releases = json_decode( wp_remote_retrieve_body( $response ), true );

		$filtered_releases = array_filter(
			$releases,
			function ( $release ) {
				return strpos( $release['tag_name'], 'wc-beta-tester' ) !== false;
			}
		);

		usort(
			$filtered_releases,
			function ( $a, $b ) {
				return strtotime( $b['created_at'] ) - strtotime( $a['created_at'] );
			}
		);

		$latest_release = $filtered_releases[0];

		$assets = $latest_release['assets'];
		$zip = array_filter(
			$assets,
			function ( $asset ) {
				return strpos( $asset['name'], 'zip' ) !== false;
			}
		);

		if ( count( $zip ) > 0 ) {
			return $zip[0]['browser_download_url'];
		} else {
			return false;
		}
	}
}

/**
 * Retrieve and install the WooCommerce Beta Tester Plugin.
 *
 * @throws Exception If the plugin zip file cannot be found.
 */
function add_woocommerce_beta_tester_plugin() {
	$zip_url = get_woocommerce_beta_tester_zip_url();

	if ( $zip_url ) {
		$cmd = "wp plugin install $zip_url --activate";
		add_filter(
			'jurassic_ninja_feature_command',
			function ( $s ) use ( $cmd ) {
				return "$s && $cmd";
			}
		);
	} else {
		throw new Exception( 'Could not find WooCommerce Beta Tester plugin zip file.' );
	}
}

/**
 * Installs and activates a live branch of WooCommerce on the site.
 *
 * @param string $branch_name The name of the branch to install.
 */
function add_woocommerce_live_branch( $branch_name ) {
	$cmd = "wp wc-beta-tester deactivate_woocommerce && wp wc-beta-tester install $branch_name && wp wc-beta-tester activate $branch_name";

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}
