<?php

namespace jn;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/RationalOptionPages.php';
require_once __DIR__ . '/rest-api-stuff.php';

define( 'OPTIONS_KEY', 'jurassic-ninja' );
define( 'REST_API_NAMESPACE', 'jurassic.ninja' );

use Medoo\Medoo;

function config( $key = null ) {
	$options = get_option( OPTIONS_KEY );

	if ( ! ( $options ) ) {
		throw new \Exception( 'Error Finding config variable', 1 );
	}
	// Create the array needed by ServerPilot() here so I don't have to copy/paste this around
	if ( 'serverpilot' === $key ) {
		return [
			'id' => config( 'serverpilot_client_id' ),
			'key' => config( 'serverpilot_client_key' ),
		];
	}
	return $options[ $key ];
}
// Just call it to trigger an exception if the config global is not defined
//config();

add_options_page();
add_scripts();
add_rest_api_endpoints();

$db = null;

try {
	$db = new Medoo([
		'database_type' => 'mysql',
		'database_name' => config( 'db_name' ),
		'server' => 'localhost',
		'username' => config( 'db_username' ),
		'password' => config( 'db_password' ),
	] );
} catch ( \Exception $e ) {
	$db = null;
}

function db() {
	return $db;
}

function l( $stuff ) {
	error_log( print_r( $stuff, true ) );
}

function generate_random_username() {
	$length = 4;
	return 'user' . bin2hex( random_bytes( $length ) );
}

function generate_new_user( $password ) {
	$username = generate_random_username();
	$sp = new \ServerPilot( config( 'serverpilot' ) );
	$user = $sp->sysuser_create( config( 'serverpilot_server_id' ), $username, $password );
	return $user;
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

function generate_random_password() {
	$length = 12;
	return random_string( $length );
}

function create_slug( $str, $delimiter = '-' ) {
	$slug = strtolower( trim( preg_replace( '/[\s-]+/', $delimiter, preg_replace( '/[^A-Za-z0-9-]+/', $delimiter, preg_replace( '/[&]/', 'and', preg_replace( '/[\']/', '', iconv( 'UTF-8', 'ASCII//TRANSLIT', $str ) ) ) ) ), $delimiter ) );
	return $slug;

}

function generate_random_subdomain() {
	$generator = new \Nubs\RandomNameGenerator\Alliteration();
	$slug = create_slug( $generator->getName() );
	return $slug;
}

function run_command_on_behalf( $user, $password, $cmd ) {
	$domain = config( 'domain' );
	$run = "sshpass -p $password ssh $user@$domain '$cmd'";
	return shell_exec( $run );
}

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

function enable_multisite( $user, $password, $domain, $subdomain_based = false ) {
	$wp_home = "~/apps/$user/public";
	$email = config( 'default_admin_email_address' );
	l( $domain );
	$cmd = "cd $wp_home && wp core multisite-install --title=\"My Primary WordPress Site on my Network\" --url=\"$domain\" --admin_email=\"$email\"";
	run_command_on_behalf( $user, $password, $cmd );
	run_command_on_behalf( $user, $password, "cd $wp_home && cp .htaccess .htaccess-not-multisite && cp /home/templates/multisite-htaccess .htaccess" );
}

function wait_action( $action_id ) {
	$sp = new \ServerPilot( config( 'serverpilot' ) );
	$ok = false;
	do {
		sleep( 1 );
		$status = $sp->action_info( $action_id );
		$ok = 'open' === $status->data->status ? false : true;
	} while ( ! $ok );
	return $status;
}

function enable_ssl( $app_id ) {
	$sp = new \ServerPilot( config( 'serverpilot' ) );
	$data = $sp->ssl_auto( $app_id );
	l( wait_action( $data->actionid ) );

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
		wait_action( $app->actionid );
		// log_new_site( $app->data );
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


function redirect_to_site( $data ) {
	$content = '';
	if ( ! $data ) {
		die( 'error' );
	}
	$url = 'http://' . $data->domains[0];

	header( 'Location: ' . $url );
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


function expired_sites() {
	global $db;
	$interval = config( 'sites_expiration' );
	return db()->query( "select * from sites where last_logged_in IS NOT NULL AND last_logged_in < DATE_SUB( NOW(), $interval )" )->fetchAll();
}

function sites_never_logged_in() {
	global $db;
	$interval = config( 'sites_never_logged_in_expiration' );
	return db()->query( "select * from sites where last_logged_in is NULL and created < DATE_SUB( NOW(), $interval )" )->fetchAll();
}

function sites_never_checked_in() {
	global $db;
	$interval = config( 'sites_never_checked_in_expiration' );
	return db()->query( "select * from sites where checked_in is NULL and created < DATE_SUB( NOW(), $interval )" )->fetchAll();
}

function log_new_site( $data ) {
	global $db;

	db()->insert('sites',
		[
			'username' => $data->name,
			'domain' => $data->domains[0],
			'#created' => 'NOW()',
		]
	);
	l( db()->error() );
}

function delete_sysuser( $id ) {
	$sp = new \ServerPilot( config( 'serverpilot' ) );
	return $sp->sysuser_delete( $id );
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

function extend_site_life( $domain ) {
	global $db;

	db()->update( 'sites',
		[
			'#last_logged_in' => 'NOW()',
		], [
			'domain' => $domain,
		]
	);
	l( db()->error() );
}

function mark_site_as_checked_in( $domain ) {
	global $db;

	db()->update( 'sites',
		[
			'#checked_in' => 'NOW()',
		], [
			'domain' => $domain,
		]
	);
	l( db()->error() );
}

function log_purged_site( $data ) {
	global $db;
	db()->insert('purged', [
		'username' => $data['username'],
		'domain' => $data['domain'],
		'created' => $data['created'],
		'last_logged_in' => $data['last_logged_in'],
		'checked_in' => $data['checked_in'],
	] );
	db()->delete( 'sites', [
		'AND' => [
			'username' => $data['username'],
			'domain' => $data['domain'],
		],
	] );
	l( db()->error() );
}

function add_options_page() {
	$options_page = new \RationalOptionPages( [
		'jurassic-ninja' => array(
			'page_title' => __( 'Jurassic Ninja Settings', 'jurassic-ninja' ),
			'menu_slug' => 'jurassic_ninja',
			'sections' => array(
				'domain' => array(
					'title' => __( 'Sites', 'jurassic-ninja' ),
					'text' => '<p>' . __( 'Configure ServerPilot client Id and Key', 'jurassic-ninja' ) . '</p>',
					'fields' => array(
						'domain' => array(
							'id' => 'domain',
							'title' => __( 'The main domain for your WordPresses', 'jurassic-ninja' ),
							'text' => __( 'Every created site will be created with a subodmain xxx.jurassic.ninja' ),
							'placeholder' => 'jurassic.ninja',
						),
						'default_admin_email_address' => array(
							'id' => 'default_admin_email_address',
							'title' => __( 'Default Admin Email Address for each WordPress', 'sample-domain' ),
							'type' => 'email',
							'placeholder' => 'test@test.com',
						),
						'sites_expiration' => array(
							'id' => 'sites_expiration',
							'title' => __( 'Sites lifespan', 'sample-domain' ),
							'text' => __( 'Default interval for considering a site to be expired', 'sample-domain' ),
							'placeholder' => 'INTERVAL 7 DAY',
						),
						'sites_never_logged_in_expiration' => array(
							'id' => 'sites_never_logged_in_expiration',
							'title' => __( 'Unlogged sites lifespan', 'sample-domain' ),
							'text' => __( 'Default interval for considering a site to be expired if the admin never logged in again wp-admin', 'sample-domain' ),
							'placeholder' => 'INTERVAL 7 DAY',
						),
						'sites_never_checked_in_expiration' => array(
							'id' => 'sites_never_checked_in_expiration',
							'title' => __( 'Unvisited sites lifespan', 'sample-domain' ),
							'text' => __( 'Default interval for considering a site to be expired if the admin never visited wp-admin', 'sample-domain' ),
							'placeholder' => 'INTERVAL 2 HOUR',
						),
					),
				),
				'serverpilot' => array(
					'title' => __( 'ServerPilot Configuration', 'jurassic-ninja' ),
					'text' => '<p>' . __( 'Configure ServerPilot client Id and Key', 'jurassic-ninja' ) . '</p>',
					'fields' => array(
						'serverpilot_server_id' => array(
							'id' => 'serverpilot_server_id',
							'title' => __( 'ServerPilot Server Id', 'jurassic-ninja' ),
							'text' => __( 'Your ServerPilot Server.' ),
						),
						'serverpilot_client_id' => array(
							'id' => 'serverpilot_client_id',
							'title' => __( 'ServerPilot Client Id', 'jurassic-ninja' ),
							'text' => __( 'Your ServerPilot Client id.' ),
						),
						'serverpilot_client_key' => array(
							'id' => 'serverpilot_client_key',
							'title' => __( 'ServerPilot Key', 'jurassic-ninja' ),
							'text' => __( 'Your ServerPilot Client key.' ),
						),
					),
				),
				'db' => array(
					'title' => __( 'Management Database', 'jurassic-ninja' ),
					'text' => '<p>' . __( 'Configure MySQL user and password', 'jurassic-ninja' ) . '</p>',
					'fields' => array(
						'db_username' => array(
							'id' => 'db_username',
							'title' => __( 'MySQL username', 'jurassic-ninja' ),
							'text' => __( 'Just a username.' ),
						),
						'db_password' => array(
							'id' => 'db_password',
							'title' => __( 'MySQL password', 'jurassic-ninja' ),
							'text' => __( 'Just a password.' ),
							'type' => 'password',
						),
						'db_name' => array(
							'id' => 'db_name',
							'title' => __( 'MySQL database name', 'jurassic-ninja' ),
							'text' => __( 'Just a database name.' ),
						),
					),
				),
			),
		),
	] );
}

function add_scripts() {
	add_action( 'wp_enqueue_scripts', function () {
		wp_enqueue_script( 'jurassicninja.js', plugins_url( '', __FILE__ ) . '/../jurassicninja.js', false, false, true );
	} );
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
}
