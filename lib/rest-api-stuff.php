<?php

namespace jn;

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

function add_endpoint( $namespace, $path, $callback, $register_rest_route_options ) {
	// Wrap the $callback passed to catch every Exception that could be thrown in it
	$wrapit = function ( \WP_REST_Request $request ) use ( $callback ) {
		// We'll wrap whatever the $callback returns
		// so we can report Exception errors in every response.
		$response = [];

		try {
			global $response;
			$data = $callback( $request );

			$response['status'] = 'ok';
			$response['data'] = $data;
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
