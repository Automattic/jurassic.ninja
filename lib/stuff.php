<?php

namespace jn;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/RationalOptionPages.php';
require_once __DIR__ . '/rest-api-stuff.php';

define( 'OPTIONS_KEY', 'jurassic-ninja' );
define( 'REST_API_NAMESPACE', 'jurassic.ninja' );

$serverpilot_instance = null;

/**
 * Force the site to log the creator in on the first time they visit the site
 * @param string $user     System user for ssh.
 * @param string $password System password for ssh.
 */
function add_auto_login( $user, $password ) {
	$domain = config( 'domain' );
	$wp_home = "~/apps/$user/public";
	$cmd = "cd $wp_home && wp option add auto_login 1 && wp option add jurassic_ninja_admin_password '$password'";
	run_command_on_behalf( $user, $password, $cmd );
	add_companion_plugin( $user, $password );
}

/**
 * Install and activates the Jurassic Ninja companion plugin on the site.
 * @param string $user     System user for ssh.
 * @param string $password System password for ssh.
 */
function add_companion_plugin( $user, $password ) {
	$wp_home = "~/apps/$user/public";
	$companion_api_base_url = rest_url( 'jurassic.ninja' );
	$companion_plugin_url = 'https://github.com/oskosk/companion/archive/master.zip';
	run_command_on_behalf( $user, $password, "cd $wp_home && wp option add  companion_api_base_url '$companion_api_base_url'" );
	$cmd = "cd $wp_home && wp plugin install --force $companion_plugin_url && wp plugin activate companion" ;
	run_command_on_behalf( $user, $password, $cmd );
}

/**
 * Install and activates Jetpack on the site.
 * @param string $user     System user for ssh.
 * @param string $password System password for ssh.
 */
function add_jetpack( $user, $password ) {
	$wp_home = "~/apps/$user/public";
	run_command_on_behalf( $user, $password, "cd $wp_home && wp plugin install jetpack && wp plugin activate jetpack" );
}

/**
* Install and activates Jetpack Beta Tester plugin on the site.
 * @param string $user     System user for ssh.
 * @param string $password System password for ssh.
 */
function add_jetpack_beta_plugin( $user, $password ) {
	$wp_home = "~/apps/$user/public";
	$jetpack_beta_plugin_url = 'https://github.com/Automattic/jetpack-beta/archive/master.zip';
	$cmd = "cd $wp_home && wp plugin install $jetpack_beta_plugin_url && wp plugin activate jetpack-beta" ;
	run_command_on_behalf( $user, $password, $cmd );
}

/**
 * Creates a new WordPress instance on the managed server
 * @param  string  $php_version      The PHP runtime versino to run the app on.
 * @param  boolean $add_ssl          Should we add SSL for the site?
 * @param  boolean $add_jetpack      Should we add Jetpack to the site?
 * @param  boolean $add_jetpack_beta Should we add Jetpack Beta Tester plugin to the site?
 * @param  boolean $enable_subdir_multisite Should we enable subdir-based multisite on the site?
 * @param  boolean $enable_subdir_multisite Should we enable subdomain-based multisite on the site?
 * @return ?Array                    null or the app data as returned by ServerPilot's API on creation.
 */
function create_wordpress( $php_version = 'php5.6', $add_ssl = false, $add_jetpack = false, $add_jetpack_beta = false, $enable_subdir_multisite = false, $enable_subdomain_multisite = false ) {
	$defaults = [
		'runtime' => 'php5.6',
		'ssl' => false,
		'jetpack' => false,
		'jetpack-beta' => false,
		'subdir_multisite' => false,
		'subdomain_multisite' => false,
	];
	$options = array_merge( $defaults, [
		'runtime' => $php_version,
		'ssl' => $add_ssl,
		'jetpack' => $add_jetpack,
		'jetpack-beta' => $add_jetpack_beta,
		'subdir_multisite' => $enable_subdir_multisite,
		'subdomain_multisite' => $enable_subdomain_multisite,
	] );

	if ( $enable_subdir_multisite && $enable_subdomain_multisite ) {
		throw new \Exception( 'not-both-multisite-types', __( "Don't try to enable both types of multiste" ) );
	}

	$sp = sp();

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
		// If creating a subdomain based multisite, we need to tell ServerPilot that the app as a wildcard subdomain.
		$domain_arg = $enable_subdomain_multisite ? array( $domain, '*.' . $domain ) : array( $domain );
		$app = $sp->app_create( $user->data->name, $user->data->id, $php_version, $domain_arg, $wordpress_options );
		wait_for_serverpilot_action( $app->actionid );
		log_new_site( $app->data );
		if ( $add_ssl ) {
			enable_ssl( $app->data->id );
		}
		if ( $add_jetpack ) {
			add_jetpack( $user->data->name, $password );
		}
		if ( $add_jetpack_beta ) {
			add_jetpack_beta_plugin( $user->data->name, $password );
		}
		add_auto_login( $user->data->name, $password );

		$sp->sysuser_update( $user->data->id, null );

		if ( $enable_subdir_multisite ) {
			enable_subdir_multisite( $user->data->name, $password, $domain );
		}
		if ( $enable_subdomain_multisite ) {
			enable_subdomain_multisite( $user->data->name, $password, $domain );
		}
		return $app->data;
	} catch ( \ServerPilotException $e ) {
		// echo $e->getCode() . ': ' .$e->getMessage();
		return null;
	}

}

/**
 * Create a slug from a string
 * @param  string $str       The string to slugify
 * @param  string $delimiter Character to use between words
 * @return string            Slugified version of the string.
 */
function create_slug( $str, $delimiter = '-' ) {
	$slug = strtolower( trim( preg_replace( '/[\s-]+/', $delimiter, preg_replace( '/[^A-Za-z0-9-]+/', $delimiter, preg_replace( '/[&]/', 'and', preg_replace( '/[\']/', '', iconv( 'UTF-8', 'ASCII//TRANSLIT', $str ) ) ) ) ), $delimiter ) );
	return $slug;

}

/**
 * Deletes a system user on the managed ServerPilot.
 * This deletes also all of the databases and WordPress instances of the user
 * @param  string $id The ServerPilot identifier for this user
 * @return [type]     [description]
 */
function delete_sysuser( $id ) {
	$sp = sp();
	return $sp->sysuser_delete( $id );
}

/**
 * Enables subdir-based multisite on a WordPress instance
 * @param string $user              System user for ssh.
 * @param string $password          System password for ssh.
 * @param string  $domain          The main domain for the site
 * @return [type]                   [description]
 */
function enable_subdir_multisite( $user, $password, $domain ) {
	$wp_home = "~/apps/$user/public";
	$email = config( 'default_admin_email_address' );
	l( $domain );
	$cmd = "cd $wp_home && wp core multisite-install --title=\"My Primary WordPress Site on my subdir-based Network\" --url=\"$domain\" --admin_email=\"$email\" --skip-email";
	run_command_on_behalf( $user, $password, $cmd );
	run_command_on_behalf( $user, $password, "cd $wp_home && cp .htaccess .htaccess-not-multisite && wget 'https://gist.githubusercontent.com/oskosk/f5febd1bb65a2ace3d35feac949b47fd/raw/6ea8ffa013056f6793d3e8775329ec74d3304835/gistfile1.txt' -O .htaccess" );
}

/**
 * Enables subdomain-based multisite on a WordPress instance
 * @param string $user              System user for ssh.
 * @param string $password          System password for ssh.
 * @param string  $domain          The main domain for the site.
 * @return [type]                   [description]
 */
function enable_subdomain_multisite( $user, $password, $domain ) {
	$wp_home = "~/apps/$user/public";
	$email = config( 'default_admin_email_address' );
	l( $domain );
	$cmd = "cd $wp_home && wp core multisite-install --title=\"My Primary WordPress Site on my subdomain-based Network\" --url=\"$domain\" --admin_email=\"$email\" --subdomains --skip-email";
	run_command_on_behalf( $user, $password, $cmd );
	run_command_on_behalf( $user, $password, "cd $wp_home && cp .htaccess .htaccess-not-multisite && wget 'https://gist.githubusercontent.com/oskosk/8cac852c793df5e4946463e2e55dfdd6/raw/a60ce4122a69c1dd36c623c9b999c36c9c8d3db8/gistfile1.txt' -O .htaccess" );
	// For some reason, the option auto_login gets set to 0, like if there were a sort of inside login happening magically.
	run_command_on_behalf( $user, $password, "cd $wp_home && wp option update auto_login 1" );
}

/**
 * Tries to enable SSL on a ServerPilot app
 * This is currently not working so well due to the amount
 * of instances created by ServerPilot and the throttling mechanism
 * enforced by Let's Encrypt.
 *
 * @param  string $app_id The ServerPilot id for the app
 * @return [type]         [description]
 */
function enable_ssl( $app_id ) {
	$sp = sp();
	$data = $sp->ssl_auto( $app_id );
	l( wait_for_serverpilot_action( $data->actionid ) );

}

/**
 * Returns the list of sites that are calculated to have expired
 * @return Array List of sites
 */
function expired_sites() {
	$interval = config( 'sites_expiration', 'INTERVAL 7 DAY' );
	return db()->get_results(
		"select * from sites where ( last_logged_in IS NOT NULL AND last_logged_in < DATE_SUB( NOW(), $interval ) )
		OR ( last_logged_in is NULL and created < DATE_SUB( NOW(), $interval ) )",
		\ARRAY_A
	);
}

/**
 * Extends the expiration date for a site
 * @param  string $domain The name of the site.
 * @return [type]         [description]
 */
function extend_site_life( $domain ) {
	db()->update( 'sites',
		[
			'last_logged_in' => current_time( 'mysql', 1 ),
		], [
			'domain' => $domain,
		]
	);
	l( db()->last_error );
}

/**
 * Given an array of domains as ServerPilot returns in its API endpoint for an app
 * excludes, the wildcard entries from the array and returns it.
 * @param  Array  $domains The array of domains for an app as returned by ServerPilot's API
 * @return string          The main domain
 */
function figure_out_main_domain( $domains ) {
	$valid = array_filter( $domains, function ( $domain ) {
		return false === strpos( $domain, '*.' );
	} );
	// reset() trick to get first item
	return reset( $valid );
}

/**
 * Generates a new username with a pseudo random name on the managed server.
 * @param  string $password The password to be assigned for the user
 * @return [type]           [description]
 */
function generate_new_user( $password ) {
	$username = generate_random_username();
	$sp = sp();
	$user = $sp->sysuser_create( config( 'serverpilot_server_id' ), $username, $password );
	return $user;
}

/**
 * Generates a random string of 12 characters.
 * @return string A string with random characters to be used as password for the WordPress administrator
 */
function generate_random_password() {
	$length = 12;
	return random_string( $length );
}

/**
 * Generates a random subdomain based on an adjective and sustantive.
 * Tne name is slugified.
 *
 * @return string A slugified subdomain.
 */
function generate_random_subdomain() {
	$generator = new \Nubs\RandomNameGenerator\Alliteration();
	$slug = create_slug( $generator->getName() );
	return $slug;
}

/**
 * Generates a random username starting with userxxxxx
 * @return string A randome username
 */
function generate_random_username() {
	$length = 4;
	return 'user' . bin2hex( random_bytes( $length ) );
}

/**
 * Attempts to log whatever it's feeded by using error_log and printf
 * @param  mixed $stuff  Whatever
 * @return [type]        [description]
 */
function l( $stuff ) {
	error_log( print_r( $stuff, true ) );
}

/**
 * Stores a record for a freshly created site
 * @param  Array $data Site data as returned by ServerPilot's API on creation
 * @return [type]       [description]
 */
function log_new_site( $data ) {
	db()->insert( 'sites',
		[
			'username' => $data->name,
			'domain' => $data->domains[0],
			'created' => current_time( 'mysql', 1 ),
		]
	);
	l( db()->last_error );
}

/**
 * Stores a record for a purged site
 * @param  Array $data Site data as returned by a query to the sites table
 * @return [type]       [description]
 */
function log_purged_site( $data ) {
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

/**
 * Updates the record for the site in the sites table indicating
 * that the creator has at least visited wp-admin once (the first time)
 * @param  string $domain The name of the site
 * @return [type]         [description]
 */
function mark_site_as_checked_in( $domain ) {
	db()->update( 'sites',
		[
			'checked_in' => current_time( 'mysql', 1 ),
		], [
			'domain' => $domain,
		]
	);
	l( db()->last_error );
}

/**
 * Deletes the system users (and thus the site and its database)
 * for which their sites have been detected as expired, or never used.
 *
 * @return [type] [description]
 */
function purge_sites() {
	$sites = sites_to_be_purged();
	$sp = sp();
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

/**
 * Runs a command on the manager server using the username and password for
 * a freshly created system user.
 * @param string $user     System user for ssh.
 * @param string $password System password for ssh.
 * @param string $cmd      The command to run on the shell
 * @return string          The command output
 */
function run_command_on_behalf( $user, $password, $cmd ) {
	$domain = config( 'domain' );
	$run = "SSHPASS=$password sshpass -e ssh -oStrictHostKeyChecking=no $user@$domain '$cmd'";
	return shell_exec( $run );
}

/**
 * Calculates and returns sites that the creator has never visited.
 * @return [type] [description]
 */
function sites_never_checked_in() {
	$interval = config( 'sites_never_checked_in_expiration', 'INTERVAL 1 HOUR' );
	return db()->get_results( "select * from sites where checked_in is NULL and created < DATE_SUB( NOW(), $interval )", \ARRAY_A );
}

/**
 * Calculates and returns sites on which the creator has never logged in with credentials.
 * The sites include:
 *     expired_sites + sites_never_checked_in + sites_never_logged_in
 *
 * @return Array The list of sites that can be purged.
 */
function sites_to_be_purged() {
	$expired = expired_sites();
	// TODO BETTER STRATEGY FOR WIPING OUT EARLY THOSE SITES THAT NEVER GOT VISITED AT ALL
	// CURRENTLY THE last_logged_in datetime is filled if the user logs in with user/password
	// and not on the first time they reach the site's dashboard.
	$unused = sites_never_checked_in();
	return array_merge( $expired, $unused );
}

/**
 * Returns a ServerPilot instance
 * @return [type] [description]
 */
function sp() {
	global $serverpilot_instance;
	if ( ! $serverpilot_instance ) {
		try {
			$serverpilot_instance = new \ServerPilot( config( 'serverpilot' ) );
		} catch ( \ServerPilotException $e ) {
			push_error( $e );
		}
	}
	return $serverpilot_instance;
}
/**
 * Locks the process by looping until ServerPilots says the action is completed
 * @param  string $action_id The ServerPilot Id for an action
 * @return string            The status of the action
 */
function wait_for_serverpilot_action( $action_id ) {
	$sp = sp();
	$ok = false;
	do {
		sleep( 1 );
		$status = $sp->action_info( $action_id );
	} while ( 'open' === $status->data->status );
	return $status;
}
