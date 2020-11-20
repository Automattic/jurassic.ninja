<?php

namespace jn;

use WP_Error;

/**
 * Get the OAuth2 token for Licensing API usage.
 *
 * @return string OAuth2 Token.
 */
function oauth2_token() {
	return (string) settings( 'jetpack_licensing_api_oauth2_token', '' );
}

/**
 * Sanitize an array of raw product slugs.
 *
 * @param string[] $products Array of raw product slugs.
 * @return string[] Array of sanitized product slugs.
 */
function sanitize_products( $products ) {
	$products = array_map( 'sanitize_text_field', $products );
	$products = array_map( 'trim', $products );
	$products = array_filter( $products );

	return $products;
}

/**
 * Get all Jetpack product families that we can issue licenses for from the API.
 *
 * @return array|WP_Error The available products.
 */
function fetch_product_families() {
	$response = wp_remote_get( 'https://public-api.wordpress.com/wpcom/v2/jetpack-licensing/product-families' );
	$status   = wp_remote_retrieve_response_code( $response );
	$body     = wp_remote_retrieve_body( $response );

	if ( 200 !== $status ) {
		return new WP_Error(
			'get_products_request_failed',
			// Translators: %s = error message returned by an API
			sprintf( __( 'Get products request failed with: %s', 'jurassic-ninja' ), $body ),
			[
				'status' => $status,
			]
		);
	}

	return json_decode( $body );
}

/**
 * Get all Jetpack product families that we can issue licenses for.
 *
 * @return array The available products.
 */
function get_product_families() {
	$cache_key = 'jurassic_ninja:jetpack_licensing_products';
	$cache     = get_transient( $cache_key );

	if ( $cache ) {
		return $cache;
	}

	$families = fetch_product_families();

	if ( is_wp_error( $families ) || empty( $families ) ) {
		// Fallback to a hardcoded list in case communication with the API fails.
		return [
			(object) [
				'name' => 'Jetpack Scan',
				'slug' => 'jetpack-scan',
				'products' => [
					(object) [
						'name' => 'Jetpack Scan Daily',
						'slug' => 'jetpack-scan',
					],
				],
			],
			(object) [
				'name' => 'Jetpack Backup',
				'slug' => 'jetpack-backup',
				'products' => [
					(object) [
						'name' => 'Jetpack Backup (Daily)',
						'slug' => 'jetpack-backup-daily',
					],
					(object) [
						'name' => 'Jetpack Backup (Real-time)',
						'slug' => 'jetpack-backup-realtime',
					],
				],
			],
			(object) [
				'name' => 'Jetpack Anti Spam',
				'slug' => 'jetpack-anti-spam',
				'products' => [
					(object) [
						'name' => 'Jetpack Anti-Spam',
						'slug' => 'jetpack-anti-spam',
					],
				],
			],
			(object) [
				'name' => 'Jetpack Plans',
				'slug' => 'jetpack-plans',
				'products' => [
					(object) [
						'name' => 'Jetpack Free',
						'slug' => 'free',
					],
					(object) [
						'name' => 'Jetpack Personal',
						'slug' => 'personal',
					],
					(object) [
						'name' => 'Jetpack Premium',
						'slug' => 'premium',
					],
					(object) [
						'name' => 'Jetpack Professional',
						'slug' => 'professional',
					],
				],
			],
		];
	}

	set_transient( $cache_key, $families, 60 * 60 * 24 );

	return $families;
}

/**
 * Issue a license.
 *
 * @param string $product Product slug for the license.
 * @return string|WP_Error The issued license key.
 */
function issue_license( $product ) {
	$response = wp_remote_post(
		'https://public-api.wordpress.com/wpcom/v2/jetpack-licensing/license',
		[
			'method'  => 'POST',
			'headers' => [
				'Authorization' => 'Bearer ' . oauth2_token(),
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode(
				[
					'product' => $product,
				]
			),
		]
	);

	$status = wp_remote_retrieve_response_code( $response );
	$body   = wp_remote_retrieve_body( $response );

	if ( 200 !== $status ) {
		return new WP_Error(
			'issue_license_request_failed',
			// Translators: %s = error message returned by an API
			sprintf( __( 'Issue license request failed with: %s', 'jurassic-ninja' ), $body ),
			[
				'status' => $status,
			]
		);
	}

	$license = json_decode( $body );

	if ( ! isset( $license->license_key ) ) {
		return new WP_Error(
			'invalid_license_response',
			// Translators: %s = invalid response returned by an API
			sprintf( __( 'Invalid license response received: %s', 'jurassic-ninja' ), $body ),
			[
				'status' => 500,
			]
		);
	}

	return json_decode( $body )->license_key;
}

/**
 * Issue multiple licenses.
 *
 * @param string[] $products Product slugs to issue licenses for.
 * @return string[]|WP_Error The issued license keys or a WP_Error instance if any license fails.
 */
function issue_licenses( $products ) {
	$issued = [];
	$failed = [];

	foreach ( $products as $product ) {
		$license_key = issue_license( $product );

		if ( ! is_wp_error( $license_key ) ) {
			$issued[] = $license_key;
		} else {
			$failed[] = $license_key;
		}
	}

	if ( ! empty( $failed ) ) {
		$error = new WP_Error(
			'failed_to_issue_jetpack_licenses',
			// Translators: %d = number of license issue failures
			sprintf( __( 'Failed to issue %d Jetpack license(s). Refer to the console for more information.', 'jurassic-ninja' ), count( $failed ) ),
			[
				'status' => 400,
			]
		);

		foreach ( $failed as $index => $failure ) {
			$error->add( "issue_license_error_$index", $failure->get_error_code() . ': ' . $failure->get_error_message(), $failure->get_error_data() );
		}

		return $error;
	}

	return $issued;
}

/**
 * Revoke a license.
 *
 * @param string $license_key License to revoke.
 * @return string|WP_Error The revoked license key or a WP_Error instance on failure.
 */
function revoke_license( $license_key ) {
	$response = wp_remote_post(
		add_query_arg(
			[ 'license_key' => $license_key ],
			'https://public-api.wordpress.com/wpcom/v2/jetpack-licensing/license'
		),
		[
			'method'  => 'DELETE',
			'headers' => [
				'Authorization' => 'Bearer ' . oauth2_token(),
			],
		]
	);

	$status = wp_remote_retrieve_response_code( $response );
	$body   = wp_remote_retrieve_body( $response );

	if ( 200 !== $status ) {
		return new WP_Error(
			'revoke_license_request_failed',
			// Translators: %s = error message returned by an API
			sprintf( __( 'Revoke license request failed with: %s', 'jurassic-ninja' ), $body ),
			[
				'status' => $status,
			]
		);
	}

	$license = json_decode( $body );

	if ( ! isset( $license->license_key ) ) {
		return new WP_Error(
			'invalid_license_response',
			// Translators: %s = invalid response returned by an API
			sprintf( __( 'Invalid license response received: %s', 'jurassic-ninja' ), $body ),
			[
				'status' => 500,
			]
		);
	}

	return json_decode( $body )->license_key;
}

/**
 * Revoke multiple licenses.
 *
 * @param string[] $license_keys License keys to revoke.
 * @return string[]|WP_Error The revoked license keys or a WP_Error instance if any revoke request fails.
 */
function revoke_licenses( $license_keys ) {
	$revoked = [];
	$failed  = [];

	foreach ( $license_keys as $license_key ) {
		$license_key = revoke_license( $license_key );

		if ( ! is_wp_error( $license_key ) ) {
			$revoked[] = $license_key;
		} else {
			$failed[] = $license_key;
		}
	}

	if ( ! empty( $failed ) ) {
		$error = new WP_Error(
			'failed_to_revoke_jetpack_licenses',
			// Translators: %d = number of license issue failures
			sprintf( __( 'Failed to revoke %d Jetpack license(s). Refer to the console for more information.', 'jurassic-ninja' ), count( $failed ) ),
			[
				'status' => 400,
			]
		);

		foreach ( $failed as $index => $failure ) {
			$error->add( "revoke_license_error_$index", $failure->get_error_code() . ': ' . $failure->get_error_message(), $failure->get_error_data() );
		}

		return $error;
	}

	return $revoked;
}

/**
 * Hook into requested site features to issue Jetpack licenses.
 */
add_action( 'jurassic_ninja_rest_create_request_features', function ( $features, $json_params ) {
	if ( empty( oauth2_token() ) ) {
		return $features;
	}

	$products = isset( $json_params['jetpack-products'] ) && is_array( $json_params['jetpack-products'] ) ? $json_params['jetpack-products'] : [];
	$products = sanitize_products( $products );

	if ( empty( $products ) ) {
		return $features;
	}

	$features['jetpack-products'] = issue_licenses( $products );

	if ( ! is_wp_error( $features['jetpack-products'] ) ) {
		$cmd = sprintf(
			"wp option update jetpack_licenses '\"'\"'%s'\"'\"' --format=json",
			wp_json_encode( $features['jetpack-products'] )
		);

		add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}, 20 );
	}

	return $features;
}, 10, 2 );

/**
 * Hook into site purging to revoke any issued Jetpack licenses.
 */
add_action( 'jurassic_ninja_purge_site', function ( $site, $user ) {
	$command = "cd ~/apps/{$user->name}/public && wp option get jetpack_licenses --format=json";

	debug( '%s: Running commands %s', $user->id, $command );
	$return = run_command_on_behalf( $site['username'], $site['password'], $command );

	if ( is_wp_error( $return ) ) {
		debug( 'There was an error fetching Jetpack licenses for user %s: (%s) - %s',
			$user->id,
			$return->get_error_code(),
			$return->get_error_message()
		);
		return;
	}

	$licenses = json_decode( trim( implode( "\n", $return ) ) );

	if ( empty( $licenses ) ) {
		// No licenses to revoke.
		return;
	}

	debug( '%s: Revoking Jetpack licenses %s', $user->id, implode( ',', $licenses ) );
	$revoked = revoke_licenses( $licenses );

	if ( is_wp_error( $revoked ) ) {
		foreach ( $revoked->get_error_codes() as $code ) {
			debug( 'There was an error revoking Jetpack licenses for user %s: (%s) - %s',
				$user->id,
				$code,
				$revoked->get_error_message( $code )
			);
		}
	}
}, 10, 2 );

/**
 * Register a shortcode which renders Jetpack Licensing controls suitable for SpecialOps usage.
 */
add_shortcode( 'jn_jetpack_products_list', function () {
	$families = get_product_families();
	ob_start();
	?>
	<style>
		.jn-jetpack-products-list {
			margin: 0 0 32px 32px;
		}

		.jn-jetpack-products-list ul {
			list-style-type: none;
			display: grid;
			grid-template-columns: 1fr 1fr;
		}

		.jn-jetpack-products-list ul,
		.jn-jetpack-products-list li {
			margin: 0;
			padding: 0;
		}
	</style>
	<div class="jn-jetpack-products-list">
		<label><?php esc_html_e( 'Optionally, select Jetpack products to issue licenses for:', 'jurassic-ninja' ); ?></label>

		<ul>
			<?php foreach ( $families as $family ) : ?>
				<?php foreach ( $family->products as $product ) : ?>
					<li>
						<label>
							<input type="checkbox" data-feature="jetpack-products" value="<?php echo esc_attr( $product->slug ); ?>"/> <?php echo esc_html( $product->name ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</ul>

		<label><?php esc_html_e( 'Other:', 'jurassic-ninja' ); ?></label>
		<input type="text" data-feature="jetpack-products" value="" placeholder="<?php esc_attr_e( 'Comma-separated list of Jetpack products', 'jurassic-ninja' ); ?>" />
	</div>
	<?php
	return ob_get_clean();
} );
