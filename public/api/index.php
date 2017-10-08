<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../lib/stuff.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

session_start();

$app = new \Slim\App;

// Register with container
$container = $app->getContainer();
$container['csrf'] = function ( $c ) {
	return new \Slim\Csrf\Guard;
};

$app->post( '/create', rest_api_create );
$app->post( '/extend/{domain}', rest_api_extend );
$app->post( '/checkin/{domain}', rest_api_check_in );
$app->post( '/purge', rest_api_purge );
$app->get( '/expiration/{domain}', rest_api_site_expiration_time );


$app->get('/anticsrf',function ( $request, $response, $args ) {
	$name_key = $this->csrf->getTokenNameKey();
	$value_key = $this->csrf->getTokenValueKey();
	$name = $request->getAttribute( $name_key );
	$value = $request->getAttribute( $value_key );

	$token_array = [
		$name_key => $name,
		$value_key => $value,
	];
	return $response->write( json_encode( $token_array ) );
} )->add( $container->get( 'csrf' ) );

$app->post( '/anticsrf', function ( $request, $response, $args ) {
	return $response->withJson( [] );
} )->add( $container->get( 'csrf' ) );

$app->run();

function rest_api_create( Request $request, Response $response ) {
	$data = create_wordpress( 'php5.6', false, true, false );
	$url = 'http://' . $data->domains[0];
	$reply = array(
		'url' => $url,
	);
	return $response->withJson( $reply );
}

function rest_api_extend( Request $request, Response $response, $domain ) {
	extend_site_life( $domain );
	return $response->withJson( $domain );
}

function rest_api_check_in( Request $request, Response $response, $domain ) {
	mark_site_as_checked_in( $domain );
	return $response->withJson( $domain );
}

function rest_api_purge( Request $request, Response $response, $domain ) {
	purge_sites();
	return $response->withJson( $purged_sites );
}

function rest_api_site_expiration_time( Request $request, Response $response, $domain ) {
	$data = get_site_expiration_date();
	return $response->withJson( $data );
}
