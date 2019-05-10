<?php

namespace jn;

add_action( 'jurassic_ninja_added_rest_api_endpoints', function() {
	add_post_endpoint( 'apps', function ( $request ) {
		$body = $request->get_json_params() ? $request->get_json_params() : [];
		// phpcs:disable WordPress.WP.DeprecatedFunctions.generate_random_passwordFound
		$username = generate_random_username();
		$password = generate_random_password();
		try {
			$sysuser = create_sp_sysuser( $username, $password );
		} catch ( \Exception $e ) {
			$sysuser = new \WP_Error( $e->getCode(), $e->getMessage() );
			return $sysuser;
		}

		$appname = $sysuser->data->name;
		$php_version = 'php7.3';
		$domain = sprintf( '%s.%s', $appname, settings( 'domain' ) );
		$domain_arg = [ $domain ];
		$app = create_sp_app( $appname, $sysuser->data->id, $php_version, $domain_arg, [] );

		$output = [
			'url' => $app,
		];

		return $output;
	} );
} );

add_action( 'jurassic_ninja_init', function() {
} );
