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

		foreach ( $failed as $failure ) {
			$error->add( $failure->get_error_code(), $failure->get_error_message(), $failure->get_error_data() );
		}

		return $error;
	}

	return $issued;
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

add_shortcode( 'jn_jetpack_products_list', function () {
	$products = [
		'personal'                => 'Personal',
		'premium'                 => 'Premium',
		'professional'            => 'Professional',
		'jetpack-backup-daily'    => 'Jetpack Backup Daily',
		'jetpack-backup-realtime' => 'Jetpack Backup Realtime',
		'jetpack-scan'            => 'Jetpack Scan',
		'jetpack-anti-spam'       => 'Jetpack Anti Spam',
	];

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
	<div class="jn-jetpack-products-list" style="margin: 0 0 32px 32px;">
		<label><?php esc_html_e( 'Optionally, select Jetpack products to issue licenses for:', 'jurassic-ninja' ); ?></label>
		<ul>
			<?php foreach ( $products as $slug => $label ) : ?>
				<li>
					<label><input type="checkbox" data-feature="jetpack-products" value="<?php echo esc_attr( $slug ); ?>"/> <?php echo esc_html( $label ); ?>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
		<label><?php esc_html_e( 'Other:', 'jurassic-ninja' ); ?></label>
		<input type="text" data-feature="jetpack-products" value="" placeholder="<?php esc_attr_e( 'Comma-separated list of Jetpack products', 'jurassic-ninja' ); ?>" />
	</div>
	<?php
	return ob_get_clean();
} );
