<?php

namespace jn;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/RationalOptionPages.php';
require_once __DIR__ . '/rest-api-stuff.php';

define( 'OPTIONS_KEY', 'jurassic-ninja' );
define( 'REST_API_NAMESPACE', 'jurassic.ninja' );

function add_auto_login( $user, $password ) {
	$domain = config( 'domain' );
	$wp_home = "~/apps/$user/public";
	$cmd = "cd $wp_home && wp option add auto_login 1 && wp option add jurassic_ninja_admin_password '$password'";
	run_command_on_behalf( $user, $password, $cmd );

}

function add_companion_plugin( $user, $password ) {
	$wp_home = "~/apps/$user/public";
	$companion_plugin_url = 'https://github.com/oskosk/companion/archive/master.zip';
	$cmd = "cd $wp_home && wp plugin install --force $companion_plugin_url && wp plugin activate companion" ;
	run_command_on_behalf( $user, $password, $cmd );
}

function add_jetpack( $user, $password ) {
	$wp_home = "~/apps/$user/public";
	run_command_on_behalf( $user, $password, "cd $wp_home && wp plugin install jetpack && wp plugin activate jetpack" );
}

function add_rest_api_endpoints() {
	add_post_endpoint( 'create', function ( $request ) {
		$data = create_wordpress( 'php5.6', false, true, false );
		$url = 'http://' . $data->domains[0];

		$output = [
			'url' => $url,
		];
		return $output;
	} );

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

function add_scripts() {
	add_action( 'wp_enqueue_scripts', function () {
		wp_enqueue_script( 'jurassicninja.js', plugins_url( '', __FILE__ ) . '/../jurassicninja.js', false, false, true );
	} );
}

function create_wordpress( $php_version = 'php5.6', $add_ssl = false, $add_jetpack = false, $add_jetpack_beta = false, $enable_multisite = false ) {
	$defaults = [
		'runtime' => 'php5.6',
		'ssl' => false,
		'jetpack' => false,
		'jetpack-beta' => false,
		'multisite-subdirs' => false,
		'multisite-subdomains' => false,
	];
	$options = array_merge( $defaults, [
		'runtime' => $php_version,
		'ssl' => $add_ssl,
		'jetpack' => $add_jetpack,
		'jetpack-beta' => $add_jetpack_beta,
		'multisite-subdirs' => $enable_multisite,
	] );

	$sp = new \ServerPilot( config( 'serverpilot' ) );

	try {
		$password = generate_random_password();
		$user = generate_new_user( $password );
		$wordpress_options = array(
			'site_title' => 'My WordPress Site',
			'admin_user' => 'demo',
			'admin_password' => $password,
			'admin_email' => config( 'default_admin_email_address' ),
		);
		$domain = generate_random_subdomain() . '.' . config( 'domain' );
		$app = $sp->app_create( $user->data->name, $user->data->id, $php_version, array( $domain ), $wordpress_options );
		wait_for_serverpilot_action( $app->actionid );
		log_new_site( $app->data );
		if ( $add_ssl ) {
			enable_ssl( $app->data->id );
		}
		if ( $add_jetpack ) {
			add_jetpack( $user->data->name, $password );
		}
		add_auto_login( $user->data->name, $password );
		add_companion_plugin( $user->data->name, $password );
		$sp->sysuser_update( $user->data->id, null );
		if ( $enable_multisite ) {
			enable_multisite( $user->data->name, $password, $domain );
		}
		return $app->data;
	} catch ( \ServerPilotException $e ) {
		// echo $e->getCode() . ': ' .$e->getMessage();
		return null;
	}

}

function create_slug( $str, $delimiter = '-' ) {
	$slug = strtolower( trim( preg_replace( '/[\s-]+/', $delimiter, preg_replace( '/[^A-Za-z0-9-]+/', $delimiter, preg_replace( '/[&]/', 'and', preg_replace( '/[\']/', '', iconv( 'UTF-8', 'ASCII//TRANSLIT', $str ) ) ) ) ), $delimiter ) );
	return $slug;

}

function delete_sysuser( $id ) {
	$sp = new \ServerPilot( config( 'serverpilot' ) );
	return $sp->sysuser_delete( $id );
}

function enable_multisite( $user, $password, $domain, $subdomain_based = false ) {
	$wp_home = "~/apps/$user/public";
	$email = config( 'default_admin_email_address' );
	l( $domain );
	$cmd = "cd $wp_home && wp core multisite-install --title=\"My Primary WordPress Site on my Network\" --url=\"$domain\" --admin_email=\"$email\"";
	run_command_on_behalf( $user, $password, $cmd );
	run_command_on_behalf( $user, $password, "cd $wp_home && cp .htaccess .htaccess-not-multisite && cp /home/templates/multisite-htaccess .htaccess" );
}

function enable_ssl( $app_id ) {
	$sp = new \ServerPilot( config( 'serverpilot' ) );
	$data = $sp->ssl_auto( $app_id );
	l( wait_for_serverpilot_action( $data->actionid ) );

}

function expired_sites() {
	global $db;
	$interval = config( 'sites_expiration' );
	return db()->get_results( "select * from sites where last_logged_in IS NOT NULL AND last_logged_in < DATE_SUB( NOW(), $interval )", \ARRAY_A );
}

function extend_site_life( $domain ) {
	global $db;

	db()->update( 'sites',
		[
			'last_logged_in' => current_time( 'mysql', 1 ),
		], [
			'domain' => $domain,
		]
	);
	l( db()->last_error );
}

function generate_new_user( $password ) {
	$username = generate_random_username();
	$sp = new \ServerPilot( config( 'serverpilot' ) );
	$user = $sp->sysuser_create( config( 'serverpilot_server_id' ), $username, $password );
	return $user;
}

function generate_random_password() {
	$length = 12;
	return random_string( $length );
}

function generate_random_subdomain() {
	$generator = new \Nubs\RandomNameGenerator\Alliteration();
	$slug = create_slug( $generator->getName() );
	return $slug;
}

function generate_random_username() {
	$length = 4;
	return 'user' . bin2hex( random_bytes( $length ) );
}

function l( $stuff ) {
	error_log( print_r( $stuff, true ) );
}

function log_new_site( $data ) {
	global $db;

	db()->insert( 'sites',
		[
			'username' => $data->name,
			'domain' => $data->domains[0],
			'created' => current_time( 'mysql', 1 ),
		]
	);
	l( db()->last_error );
}

function log_purged_site( $data ) {
	global $db;
	db()->insert( 'purged', [
		'username' => $data['username'],
		'domain' => $data['domain'],
		'created' => $data['created'],
		'last_logged_in' => $data['last_logged_in'],
		'checked_in' => $data['checked_in'],
	] );
	db()->delete( 'sites', [
		'username' => $data['username'],
		'domain' => $data['domain'],
	] );
	l( db()->last_error );
}

function mark_site_as_checked_in( $domain ) {
	global $db;

	db()->update( 'sites',
		[
			'checked_in' => current_time( 'mysql', 1 ),
		], [
			'domain' => $domain,
		]
	);
	l( db()->last_error );
}

function purge_sites() {
	$sites = sites_to_be_purged();
	$sp = new \ServerPilot( config( 'serverpilot' ) );
	$system_users  = $sp->sysuser_list()->data;
	$site_users = array_map(
		function ( $site ) {
			return $site['username'];
		},
		$sites
	);
	$purge = array_filter( $system_users, function ( $user ) use ( $site_users ) {
			return in_array( $user->name, $site_users, true );
	} );
	foreach ( $purge as $user ) {
		delete_sysuser( $user->id );
	}
	foreach ( $sites as $site ) {
		log_purged_site( $site );
	}
	return array_map(
		function ( $site ) {
			return $site['domain'];
		},
		$sites
	);
}

/**
 * function to generate random strings
 * @param       int     $length number of characters in the generated string
 * @return      string          a new string is created with random characters of the desired length
 */
function random_string( $length = 32 ) {
	$randstr = null;
	srand( (double) microtime( true ) * 1000000 );
	//our array add all letters and numbers if you wish
	$chars = array_merge( range( 'a', 'z' ), range( 0, 9 ), range( 'A', 'Z' ) );

	for ( $rand = 0; $rand <= $length; $rand++ ) {
		$random = rand( 0, count( $chars ) - 1 );
		$randstr .= $chars[ $random ];
	}
	return $randstr;
}


function redirect_to_site( $data ) {
	$content = '';
	if ( ! $data ) {
		die( 'error' );
	}
	$url = 'http://' . $data->domains[0];

	header( 'Location: ' . $url );
}

function run_command_on_behalf( $user, $password, $cmd ) {
	$domain = config( 'domain' );
	$run = "sshpass -p $password ssh -oStrictHostKeyChecking=no $user@$domain '$cmd'";
	return shell_exec( $run );
}

function sites_never_checked_in() {
	global $db;
	$interval = config( 'sites_never_checked_in_expiration' );
	return db()->get_results( "select * from sites where checked_in is NULL and created < DATE_SUB( NOW(), $interval )", \ARRAY_A );
}

function sites_never_logged_in() {
	global $db;
	$interval = config( 'sites_never_logged_in_expiration' );
	return db()->get_results( "select * from sites where last_logged_in is NULL and created < DATE_SUB( NOW(), $interval )", \ARRAY_A );
}

function sites_to_be_purged() {
	$expired = expired_sites();
	// TODO BETTER STRATEGY FOR WIPING OUT EARLY THOSE SITES THAT NEVER GOT VISITED AT ALL
	// CURRENTLY THE last_logged_in datetime is filled if the user logs in with user/password
	// and not on the first time they reach the site's dashboard.
	$never_logged_in = sites_never_logged_in();
	$unused = sites_never_checked_in();
	return array_merge( $expired, $never_logged_in, $unused );
}

function wait_for_serverpilot_action( $action_id ) {
	$sp = new \ServerPilot( config( 'serverpilot' ) );
	$ok = false;
	do {
		sleep( 1 );
		$status = $sp->action_info( $action_id );
		$ok = 'open' === $status->data->status ? false : true;
	} while ( ! $ok );
	return $status;
}

