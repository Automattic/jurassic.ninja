<?php
require __DIR__ . '/../../config.inc.php';
require __DIR__ . '/../vendor/autoload.php';

use Medoo\Medoo;

$globalconfig = $CONFIG;

$db = new Medoo([
	'database_type' => 'mysql',
	'database_name' => 'sites',
	'server' => 'localhost',
	'username' => $globalconfig['db']['username'],
	'password' => $globalconfig['db']['password']
] );

function l( $stuff ) {
	error_log( print_r( $stuff, true ) );
}

function generate_random_username() {
	$length = 4;
	return 'user' . bin2hex( random_bytes( $length ) );
}

function generate_new_user( $password ) {
	global $globalconfig;
	$username = generate_random_username();
	$sp = new ServerPilot( $globalconfig['serverpilot'] );
	$user = $sp->sysuser_create( $globalconfig['SERVER_ID'], $username, $password );
	return $user;
}

/**
 * function to generate random strings
 * @param       int     $length number of characters in the generated string
 * @return      string          a new string is created with random characters of the desired length
 */
function random_string( $length = 32 ) {
	$randstr;
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
	global $globalconfig;
	$domain = $globalconfig['DOMAIN'];
	$run = "sshpass -p $password ssh $user@$domain '$cmd'";
	return shell_exec( $run );
}

function add_auto_login( $user, $password ) {
	global $globalconfig;
	$domain = $globalconfig['DOMAIN'];
	$WP_HOME = "~/apps/$user/public";
	$cmd = "cd $WP_HOME && wp option add auto_login 1 && wp option add sandbox_password '$password'";
	run_command_on_behalf( $user, $password, $cmd );

}

function copy_sandbox_plugin( $user, $password ) {
	$WP_HOME = "~/apps/$user/public";

	$cmd = "cp -a /home/sandbox $WP_HOME/wp-content/plugins/ && cd $WP_HOME && wp plugin activate sandbox" ;
	run_command_on_behalf( $user, $password, $cmd );

}

function add_jetpack( $user, $password ) {
	global $globalconfig;
	$WP_HOME = "~/apps/$user/public";
	run_command_on_behalf( $user, $password, "cd $WP_HOME && wp plugin install jetpack && wp plugin activate jetpack" );
}

function enable_multisite( $user, $password, $domain, $subdomainBase = false ) {
	global $globalconfig;
	$WP_HOME = "~/apps/$user/public";
	$email = $globalconfig['DEFAULT_ADMIN_EMAIL_ADDRESS'];
	l( $domain );
	$cmd = "cd $WP_HOME && wp core multisite-install --title=\"My Primary WordPress Site on my Network\" --url=\"$domain\" --admin_email=\"$email\"";
	run_command_on_behalf( $user, $password, $cmd );
	run_command_on_behalf( $user, $password, "cd $WP_HOME && cp .htaccess .htaccess-not-multisite && cp /home/templates/multisite-htaccess .htaccess" );
}

function wait_action( $actionId ) {
	global $globalconfig;
	$sp = new ServerPilot( $globalconfig['serverpilot'] );
	$ok = false;
	do {
		sleep( 1 );
		$status = $sp->action_info( $actionId );
		$ok = 'open' === $status->data->status ? false : true;
	} while ( ! $ok );
	return $status;
}

function enable_ssl( $appId ) {
	global $globalconfig;
	$sp = new ServerPilot( $globalconfig['serverpilot'] );
	$data = $sp->ssl_auto( $appId );
	l( wait_action( $data->actionid ) );

}

function create_wordpress( $phpVersion = 'php5.6', $addSsl = false, $addJetpack = false, $addJetpackBeta = false, $enableMultisite = false ) {
	global $globalconfig;

	$defaults = [
		'runtime' => 'php5.6',
		'ssl' => false,
		'jetpack' => false,
		'jetpack-beta' => false,
		'multisite-subdirs' => false,
		'multisite-subdomains' => false,
	];
	$options = array_merge( $defaults, [
		'runtime' => $phpVersion,
		'ssl' => $addSsl,
		'jetpack' => $addJetpack,
		'jetpack-beta' => $addJetpackBeta,
		'multisite-subdirs' => $enableMultisite,
	] );

	$sp = new ServerPilot( $globalconfig['serverpilot'] );

	try {
		$PASSWORD = generate_random_password();
		$USER = generate_new_user( $PASSWORD );
		$wpOptions = array(
			'site_title' => 'My WordPress Site',
			'admin_user' => 'demo',
			'admin_password' => $PASSWORD,
			'admin_email' => $globalconfig['DEFAULT_ADMIN_EMAIL_ADDRESS'],
		);
		$DOMAIN = generate_random_subdomain() . '.' . $globalconfig['DOMAIN'];
		$app = $sp->app_create( $USER->data->name, $USER->data->id, $phpVersion, array( $DOMAIN ), $wpOptions );
		wait_action( $app->actionid );
		log_new_site( $app->data );
		if ( $addSsl ) {
			enable_ssl( $app->data->id );
		}
		if ( $addJetpack ) {
			add_jetpack( $USER->data->name, $PASSWORD );
		}
		add_auto_login( $USER->data->name, $PASSWORD );
		copy_sandbox_plugin( $USER->data->name, $PASSWORD );
		$sp->sysuser_update( $USER->data->id, NULL );
		if ( $enableMultisite ) {
			enable_multisite( $USER->data->name, $PASSWORD, $DOMAIN );
		}
		return $app->data;
	} catch ( ServerPilotException $e ) {
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
	$INTERVAL = 'INTERVAL 1 WEEK';
	return $db->query( "select * from sites where last_logged_in IS NOT NULL AND last_logged_in < DATE_SUB( NOW(), $INTERVAL )" )->fetchAll();
}

function sites_never_logged_in() {
	global $db;
	$INTERVAL = 'INTERVAL 1 WEEK';
	return $db->query( "select * from sites where last_logged_in is NULL and created < DATE_SUB( NOW(), $INTERVAL )" )->fetchAll();
}

function sites_never_checked_in() {
	global $db;
	$INTERVAL = 'INTERVAL 10 HOUR';
	return $db->query( "select * from sites where checked_in is NULL and created < DATE_SUB( NOW(), $INTERVAL )" )->fetchAll();
}

function log_new_site( $data ) {
	global $db;

	$db->insert('sites',
		[
			'username' => $data->name,
			'domain' => $data->domains[0],
			'#created' => 'NOW()',
		]
	);
	l( $db->error() );
}

function delete_sysuser( $id ) {
	global $globalconfig;
	$sp = new ServerPilot( $globalconfig['serverpilot'] );
	return $sp->sysuser_delete( $id );
}

function purge_sites() {
	$sites = sites_to_be_purged();
	global $globalconfig;
	$sp = new ServerPilot( $globalconfig['serverpilot'] );
	$systemUsers  = $sp->sysuser_list()->data;
	$siteUsers = array_map(
		function ( $site ) {
			return $site['username'];
		},
		$sites
	);
	$purge = array_filter($systemUsers, function ( $user ) use ( $siteUsers ) {
			return in_array( $user->name, $siteUsers );
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

	$db->update( 'sites',
		[
			'#last_logged_in' => 'NOW()',
		], [
			'domain' => $domain,
		]
	);
	l( $db->error() );
}

function mark_site_as_checked_in( $domain ) {
	global $db;

	$db->update( 'sites',
		[
			'#checked_in' => 'NOW()',
		], [
			'domain' => $domain,
		]
	);
	l( $db->error() );
}

function log_purged_site( $data ) {
	global $db;
	$db->insert('purged', [
		'username' => $data['username'],
		'domain' => $data['domain'],
		'created' => $data['created'],
		'last_logged_in' => $data['last_logged_in'],
		'checked_in' => $data['checked_in'],
	] );
	$db->delete( 'sites', [
		'AND' => [
			'username' => $data['username'],
			'domain' => $data['domain'],
		],
	] );
	l( $db->error() );
}
