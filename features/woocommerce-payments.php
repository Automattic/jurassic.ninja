<?php
/**
 * WooCommerce Payments
 *
 * @package jurassic-ninja
 */

namespace jn;

const WCPAY_DEFAULTS = array(
	'woocommerce-payments-branch' => false,
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
				if ( ! isset( $features['woocommerce-payments'] ) ) {
					if ( $features['woocommerce-payments-branch'] ) {
						debug( '%s: Adding WooCommerce Payments (on the %s branch)', $domain, $features['woocommerce-payments-branch'] );
						add_woocommerce_payments_branch_plugin( $features['woocommerce-payments-branch'] );
					} elseif ( $features['woocommerce-payments-release'] ) {
						debug( '%s: Adding WooCommerce Payments (version %s)', $domain, $features['woocommerce-payments-release'] );
						add_woocommerce_payments_release_plugin( $features['woocommerce-payments-release'] );
					}
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
				if ( isset( $json_params['woocommerce-payments-branch'] ) ) {
					$features['woocommerce-payments-branch'] = $json_params['woocommerce-payments-branch'];
				}

				if ( isset( $json_params['woocommerce-payments-dev-tools'] ) ) {
					$features['woocommerce-payments-dev-tools'] = true;
				}

				if ( isset( $json_params['woocommerce-payments-jn-options'] ) ) {
					$features['woocommerce-payments-jn-options'] = true;
				}

				if ( isset( $json_params['woocommerce-payments-release'] ) ) {
					$features['woocommerce-payments-release'] = $json_params['woocommerce-payments-release'];
				}

				return $features;
			},
			10,
			2
		);
	}
);

/**
 * Builds, installs and activates the WooCommerce Payments plugin on the site
 * for a given branch.
 *
 * @param string $branch The WooCommerce Payments branch to install.
 */
function add_woocommerce_payments_branch_plugin( $branch ) {
	$cmd = 'curl https://gist.githubusercontent.com/aprea/45a7f3b3583ff65a658d303c2e5a6207/raw --output build-woocommerce-payments.sh'
		. " && source build-woocommerce-payments.sh $branch"
		. ' && wp plugin activate woocommerce-payments';

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

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
 * Installs and activates the WooCommerce Payments Dev Tools plugin on the site.
 */
function add_woocommerce_payments_dev_tools() {
	$woocommerce_payments_dev_tools_plugin_url = 'https://github.com/Automattic/woocommerce-payments-dev-tools-ci/archive/trunk.zip';
	// We install the trunk version of the plugin.
	$cmd = "wp plugin install $woocommerce_payments_dev_tools_plugin_url --activate";

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
