<?php
/**
 * REST API stuff.
 *
 * @package jurassic-ninja
 */

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

/**
 * Adds a REST interface to this plugin
 */
function add_rest_api_endpoints() {
	$permission_callback = array(
		'permission_callback' => function () {
			return settings( 'lock_launching', false ) ? is_user_logged_in() : true;
		},
	);

	add_post_endpoint(
		'create',
		function ( $request ) {
			$defaults = create_endpoint_feature_defaults();
			$json_params = $request->get_json_params();

			if ( ! settings( 'enable_launching', true ) ) {
				return new \WP_Error(
					'site_launching_disabled',
					__( 'Site launching is disabled right now', 'jurassic-ninja' ),
					array(
						'status' => 503,
					)
				);
			}

			$features = array_merge(
				$defaults,
				array(
					'shortlife' => isset( $json_params['shortlived'] ) && (bool) $json_params['shortlived'],
					'php_version' => isset( $json_params['php_version'] ) ? $json_params['php_version'] : 'default',
				)
			);

			/**
			 * Filters the features requested through the /create REST API endpoint
			 *
			 * If any filter returns a WP_Error, then the request is finished with status 500
			 *
			 * @param array $features    The current feature flags.
			 * @param array $json_params The body of the json request.
			 */
			$features = apply_filters( 'jurassic_ninja_rest_create_request_features', $features, $json_params );
			// Check if any feature errored.
			foreach ( $features as $feature ) {
				if ( is_wp_error( $feature ) ) {
					return $feature;
				}
			}

			$data = launch_wordpress( $features['php_version'], $features );
			if ( null === $data ) {
				return new \WP_Error(
					'failed_to_launch_site',
					esc_html__( 'There was an error launching the site.', 'jurassic-ninja' ),
					array(
						'status' => 500,
					)
				);
			}
			/**
			 * Filter the final URL for a site.
			 *
			 * Useful for the ssl feature that updated the URL scheme.
			 *
			 * @since 3.0
			 *
			 * @param string $domain   The domain used for the site.
			 * @param array  $features The feature with which the site was launched.
			 */
			$url = apply_filters( 'jurassic_ninja_created_site_url', figure_out_main_domain( $data->domains ), $features );

			$output = array(
				'url' => $url,
			);
			return $output;
		},
		$permission_callback
	);

	add_post_endpoint(
		'extend',
		function ( $request ) {
			$body = $request->get_json_params() ? $request->get_json_params() : array();
			if ( ! isset( $body['domain'] ) ) {
				return new \WP_Error( 'no_domain_in_body', __( 'You must pass a valid "domain" prop in the body', 'jurassic-ninja' ) );
			}
			extend_site_life( $body['domain'] );

			$output = array(
				'url' => $body['domain'],
			);

			return $output;
		}
	);

	add_post_endpoint(
		'checkin',
		function ( $request ) {
			$body = $request->get_json_params() ? $request->get_json_params() : array();
			if ( ! isset( $body['domain'] ) ) {
				return new \WP_Error( 'no_domain_in_body', __( 'You must pass a valid "domain" prop in the body', 'jurassic-ninja' ) );
			}
			mark_site_as_checked_in( $body['domain'] );

			$output = array(
				'url' => $body['domain'],
			);

			return $output;
		}
	);

	/**
	 * Done after registering the JN specific endpoints.
	 *
	 * It's here so we can hook and add more endpoints.
	 *
	 * @since 4.14
	 */
	do_action( 'jurassic_ninja_added_rest_api_endpoints' );
}

/**
 * Adds a callback to a REST endpoint for the POST method.
 * Depends on global constant REST_API_NAMESPACE.
 *
 * @param [type] $path                        New Path to add.
 * @param [type] $callback                    The function that will handle the request. Must return Array.
 * @param ?array $register_rest_route_options Either empty or a successful object.
 */
function add_post_endpoint( $path, $callback, $register_rest_route_options = array() ) {
	$namespace = REST_API_NAMESPACE;

	$options = array_merge(
		$register_rest_route_options,
		array(
			'methods' => \WP_REST_Server::CREATABLE,
		)
	);
	return add_endpoint( $namespace, $path, $callback, $options );
}

/**
 * Adds a callback to a REST endpoint for the GET method.
 * Depends on global constant REST_API_NAMESPACE.
 *
 * @param [type] $path                        New Path to add.
 * @param [type] $callback                    The function that will handle the request. Must return Array.
 * @param ?array $register_rest_route_options Either empty or a successful object.
 */
function add_get_endpoint( $path, $callback, $register_rest_route_options = array() ) {
	$namespace = REST_API_NAMESPACE;
	$options = array_merge(
		$register_rest_route_options,
		array(
			'methods' => \WP_REST_Server::READABLE,
		)
	);
	return add_endpoint( $namespace, $path, $callback, $options );
}

/**
 * Handy function to register a hook and create a REST API endpoint easily
 * Users register_rest_route()
 *
 * @param string   $namespace                   namespace for the endpoint.
 * @param string   $path                        The endpoint's path.
 * @param callable $callback                  The callback to use.
 * @param [type]   $register_rest_route_options Extra optinos to register_rest_route.
 */
function add_endpoint( $namespace, $path, $callback, $register_rest_route_options ) {
	// Wrap the $callback passed to catch every Exception that could be thrown in it.
	$wrapit = function ( \WP_REST_Request $request ) use ( $callback ) {
		// We'll wrap whatever the $callback returns
		// so we can report Exception errors in every response (third parameter).
		$response = array();

		try {
			$data = $callback( $request );
			$response['status'] = 'ok';
			$response['data'] = $data;

			if ( is_wp_error( $data ) ) {
				$response = $data;
			}
		} catch ( Exception $e ) {
			$response = array(
				'status' => 'error',
				'error' => array(
					'code' => $e->getCode(),
					'message' => $e->getMessage(),
				),
				'data' => null,
			);
		}
		return $response;
	};

	$options = array_merge(
		$register_rest_route_options,
		array(
			'callback' => $wrapit,
		)
	);

	add_action(
		'rest_api_init',
		function () use ( $namespace, $path, $options ) {
			register_rest_route( $namespace, $path, $options );
		}
	);
}

/**
 * Create feature endpoint.
 */
function create_endpoint_feature_defaults() {
	$defaults = array(
		'shortlife' => false,
	);
	/**
	 * Filters the default features coming from a REST request
	 *
	 * @since 3.0
	 *
	 * @param array  $defaults The feature with which launch_wordpress() is called.
	 */
	return apply_filters( 'jurassic_ninja_rest_feature_defaults', $defaults );
}
