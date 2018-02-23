<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

/**
 * Adds a REST interface to this plugin
 */
function add_rest_api_endpoints() {
	$permission_callback = [
		'permission_callback' => function () {
			return settings( 'lock_launching', false ) ? current_user_can( 'manage_options' ) : true ;
		},
	];

	$specialops_permission_callback = [
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	];
	add_post_endpoint( 'create', function ( $request ) {
		$defaults = create_endpoint_feature_defaults();
		$json_params = $request->get_json_params();

		if ( ! settings( 'enable_launching', true ) ) {
			return new \WP_Error( 'site_launching_disabled', __( 'Site launching is disabled right now' ), [
				'status' => 503,
			] );
		}

		$features = array_merge( $defaults, [
			'shortlife' => isset( $json_params['shortlived'] ) && (bool) $json_params['shortlived'],
		] );

		/**
		 * Filters the features requested through the /create REST API endpoint
		 *
		 * If any filter returns a WP_Error, then the request is finished with status 500
		 *
		 * @param array $features    The current feature flags.
		 * @param array $json_params The body of the json request.
		 */
		$features = apply_filters( 'jurassic_ninja_rest_create_request_features', $features, $json_params );

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		$data = launch_wordpress( 'php7.0', $features );
		if ( null === $data ) {
			return new \WP_Error(
				'failed_to_launch_site',
				esc_html__( 'There was an error launching the site.' ),
				[
					'status' => 500,
				]
			);
		}

		$url = apply_filter( 'jurassic_ninja_created_site_url', $features );

		$output = [
			'url' => $url,
		];
		return $output;
	}, $permission_callback );

	add_post_endpoint( 'specialops/create', function ( $request ) {
		$defaults = create_endpoint_feature_defaults();
		$json_params = $request->get_json_params();

		$features = $json_params && is_array( $json_params ) ? $json_params : [];
		$features = array_merge( $defaults, $features );
		if ( ! settings( 'enable_launching', true ) ) {
			return new \WP_Error( 'site_launching_disabled', __( 'Site launching is disabled right now' ), [
				'status' => 503,
			] );
		}

		$data = launch_wordpress( $features['runtime'], $features );

		if ( null === $data ) {
			return new \WP_Error(
				'failed_to_launch_site',
				esc_html__( 'There was an error launching the site.' ),
				[
					'status' => 500,
				]
			);
		}

		$url = apply_filter( 'jurassic_ninja_created_site_url', $features );

		$output = [
			'url' => $url,
		];
		return $output;
	}, $specialops_permission_callback );

	add_post_endpoint( 'extend', function ( $request ) {
		$body = $request->get_json_params() ? $request->get_json_params() : [];
		if ( ! isset( $body['domain'] ) ) {
			return new \WP_Error( 'no_domain_in_body', __( 'You must pass a valid "domain" prop in the body' ) );
		}
		extend_site_life( $body['domain'] );

		$output = [
			'url' => $body['domain'],
		];

		return $output;
	} );

	add_post_endpoint( 'checkin', function ( $request ) {
		$body = $request->get_json_params() ? $request->get_json_params() : [];
		if ( ! isset( $body['domain'] ) ) {
			return new \WP_Error( 'no_domain_in_body', __( 'You must pass a valid "domain" prop in the body' ) );
		}
		mark_site_as_checked_in( $body['domain'] );

		$output = [
			'url' => $body['domain'],
		];

		return $output;
	} );
}

/**
 * Adds a callback to a REST endpoint for the POST method.
 * Depends on global constant REST_API_NAMESPACE.
 *
 * @param [type] $path                        New Path to add
 * @param [type] $callback                    The function that will handle the request. Must return Array.
 * @param ?array  $register_rest_route_options Either empty or a succesful object
 */
function add_post_endpoint( $path, $callback, $register_rest_route_options = [] ) {
	$namespace = REST_API_NAMESPACE;

	$options = array_merge( $register_rest_route_options, [
		'methods' => \WP_REST_Server::CREATABLE,
	] );
	return add_endpoint( $namespace, $path, $callback, $options );
}

/**
 * Adds a callback to a REST endpoint for the GET method.
 * Depends on global constant REST_API_NAMESPACE.
 *
 * @param [type] $path                        New Path to add
 * @param [type] $callback                    The function that will handle the request. Must return Array.
 * @param ?array  $register_rest_route_options Either empty or a succesful object
 */
function add_get_endpoint( $path, $callback, $register_rest_route_options = [] ) {
	$namespace = REST_API_NAMESPACE;
	$options = array_merge( $register_rest_route_options, [
		'methods' => \WP_REST_Server::READABLE,
	] );
	return add_endpoint( $namespace, $path, $callback, $options );
}

/**
 * Handy function to register a hook and create a REST API endpoint easily
 * Users register_rest_route()
 *
 * @param string $namespace                   namespace for the endpoint
 * @param string $path                        The endpoint's path
 * @param callable $callback                  The callback to use
 * @param [type] $register_rest_route_options Extra optinos to register_rest_route
 */
function add_endpoint( $namespace, $path, $callback, $register_rest_route_options ) {
	// Wrap the $callback passed to catch every Exception that could be thrown in it
	$wrapit = function ( \WP_REST_Request $request ) use ( $callback ) {
		// We'll wrap whatever the $callback returns
		// so we can report Exception errors in every response (third parameter).
		$response = [];

		try {
			global $response;
			$data = $callback( $request );
			$response['status'] = 'ok';
			$response['data'] = $data;

			if ( is_wp_error( $data ) ) {
				$response = $data;
			}
		} catch ( Exception $e ) {
			global $response;
			$response = [
				'status' => 'error',
				'error' => [
					'code' => $e->getCode(),
					'message' => $e->getMessage(),
				],
				'data' => null,
			];
		}
		return $response;
	};

	$options = array_merge( $register_rest_route_options, [
		'callback' => $wrapit,
	] );

	add_action( 'rest_api_init', function () use ( $namespace, $path, $options ) {
		register_rest_route( $namespace, $path, $options );
	} );
}

function create_endpoint_feature_defaults( $defaults ) {
	$defaults = [
		'shortlife' => false,
	];
	return apply_filters( 'jurassic_ninja_create_endpoint_feature_defaults', $defaults );
}
