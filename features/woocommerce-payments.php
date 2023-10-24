<?php
/**
 * WooCommerce Payments
 *
 * @package jurassic-ninja
 */

namespace jn;

const WCPAY_DEFAULTS = array(
	'woocommerce-payments-dev-tools' => false,
	'woocommerce-payments-jn-options' => false,
	'woocommerce-payments-release' => false,
);

add_action(
	'jurassic_ninja_init',
	function () {
		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app = null, $features, $domain ) {
				$features = array_merge( WCPAY_DEFAULTS, $features );
				if ( ! isset( $features['woocommerce-payments'] ) && $features['woocommerce-payments-release'] ) {
					debug( '%s: Adding WooCommerce Payments (version %s)', $domain, $features['woocommerce-payments-release'] );
					add_woocommerce_payments_release_plugin( $features['woocommerce-payments-release'] );
				}

				if ( $features['woocommerce-payments-dev-tools'] ) {
					debug( '%s: Adding WooCommerce Payments Dev Tools', $domain );
					add_woocommerce_payments_dev_tools();
				}

				if ( $features['woocommerce-payments-jn-options'] ) {
					debug( '%s: Adding WooCommerce Payments Jurassic Ninja Options', $domain );
					add_woocommerce_payments_jurassic_ninja_options();
				}
			},
			10,
			3
		);

		add_filter(
			'jurassic_ninja_rest_feature_defaults',
			function ( $defaults ) {
				return array_merge( $defaults, WCPAY_DEFAULTS );
			}
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {
				if ( isset( $json_params['woocommerce-payments-dev-tools'] ) ) {
					$features['woocommerce-payments-dev-tools'] = $json_params['woocommerce-payments-dev-tools'];
				}

				if ( isset( $json_params['woocommerce-payments-jn-options'] ) ) {
					$features['woocommerce-payments-jn-options'] = $json_params['woocommerce-payments-jn-options'];
				}

				if ( isset( $json_params['woocommerce-payments-release'] ) ) {
					$features['woocommerce-payments-release'] = $json_params['woocommerce-payments-release'];
					if ( $features['woocommerce-payments-release'] ) {
						$features['woocommerce'] = true;
					}
				}

				return $features;
			},
			10,
			2
		);
	}
);

/**
 * Installs and activates the WooCommerce Payments plugin on the site
 * for a given release tag.
 *
 * @param string $release_tag The WooCommerce Payments release tag to install.
 */
function add_woocommerce_payments_release_plugin( $release_tag ) {
	$woocommerce_payments_release_tag_url = "https://github.com/Automattic/woocommerce-payments/releases/download/$release_tag/woocommerce-payments.zip";
	$cmd = "wp plugin install $woocommerce_payments_release_tag_url --activate";

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Gets the zip link for the WooCommerce Payments Dev Tools plugin from the private GitHub repo.
 *
 * @return string|null The zip link. Null if it can not be retrieved.
 */
function get_private_wcpay_dev_tools_zipball_link(): ?string{
	$response = wp_remote_head(
		'https://api.github.com/repos/Automattic/woocommerce-payments-dev-tools-ci/zipball/trunk',
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . settings( 'wcpay_dev_tools_github_token', '' ),
			),
		)
	);

	$status      = wp_remote_retrieve_response_code( $response );
	$location    = wp_remote_retrieve_header( $response, 'location' );

	if ( 302 === $status
		&& is_string( $location ) &&
		wp_http_validate_url ( $location )
	) {
		return $location;
	}

	push_error( new \WP_Error(
		'wcpay_dev_tools_private_repo',
		__('Can not retrieve the WooCommerce Payments Dev Tools private repo. The GitHub fined-grain token may be invalid.', 'jurassic-ninja' )
	) );
	return null;
}
/**
 * Installs and activates the WooCommerce Payments Dev Tools plugin on the site.
 */
function add_woocommerce_payments_dev_tools() {
	$private_repo_url = get_private_wcpay_dev_tools_zipball_link();
	$public_repo_url  = 'https://github.com/Automattic/woocommerce-payments-dev-tools-ci/releases/latest/download/woocommerce-payments-dev-tools-trunk.zip';

	$install_url = $private_repo_url ?? $public_repo_url;
	$cmd         = "wp plugin install $install_url --activate";

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Adds a set of WordPress options to the site to make it
 * easier to work with WooCommerce Payments on
 * Jurassic Ninja sites.
 */
function add_woocommerce_payments_jurassic_ninja_options() {
	// Disable the WPCOM request proxy and enable UPE and subscriptions features.
	$cmd = 'wp option update wcpaydev_proxy 0'
		. ' && wp option update _wcpay_feature_upe 1'
		. ' && wp option update _wcpay_feature_upe_additional_payment_methods 1'
		. ' && wp option update _wcpay_feature_subscriptions 1';

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}
