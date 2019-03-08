<?php

namespace jn;

add_action( 'jurassic_ninja_init', function() {
	$defaults = [
		'blockxmlrpc' => false,
	];

	add_action( 'jurassic_ninja_add_features_before_auto_login', function( &$app = null, $features, $domain ) use ( $defaults ) {
		$features = array_merge( $defaults, $features );
		if ( $features['blockxmlrpc'] ) {
			debug( '%s: Block XML-RPC requests to this site', $domain );
			block_xmlrpc();
		}
	}, 1, 3 );

	add_filter( 'jurassic_ninja_rest_create_request_features', function( $features, $json_params ) {
		if ( isset( $json_params['blockxmlrpc'] ) ) {
			$features['blockxmlrpc'] = $json_params['blockxmlrpc'];
		}
		return $features;
	}, 10, 2 );
} );

function block_xmlrpc() {
	$techniques = [
		// Force a 403
		'echo -e "\n\n#Block XML-RPC\n<Files xmlrpc.php>\norder deny,allow\ndeny from all\n</Files>" >> .htaccess',
		// Force a 404
		'rm xmlrpc.php',
	];
	$cmd = $techniques[ array_rand( $techniques ) ];
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
