<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
if ( ! class_exists( 'RationalOptionPages' ) ) {
	require_once __DIR__ . '/RationalOptionPages.php';
}
if ( ! class_exists( 'CustomNameGenerator' ) ) {
	require_once __DIR__ . '/class-customnamegenerator.php';
}
require_once __DIR__ . '/rest-api-stuff.php';

define( 'REST_API_NAMESPACE', 'jurassic.ninja' );
define( 'COMPANION_PLUGIN_URL', 'https://github.com/oskosk/companion/archive/master.zip' );
define( 'JETPACK_BETA_PLUGIN_URL', 'https://github.com/Automattic/jetpack-beta/archive/master.zip' );
define( 'SUBDOMAIN_MULTISITE_HTACCESS_TEMPLATE_URL', 'https://gist.githubusercontent.com/oskosk/8cac852c793df5e4946463e2e55dfdd6/raw/a60ce4122a69c1dd36c623c9b999c36c9c8d3db8/gistfile1.txt' );
define( 'SUBDIR_MULTISITE_HTACCESS_TEMPLATE_URL', 'https://gist.githubusercontent.com/oskosk/f5febd1bb65a2ace3d35feac949b47fd/raw/6ea8ffa013056f6793d3e8775329ec74d3304835/gistfile1.txt' );

/**
 * Force the site to log the creator in on the first time they visit the site
 * Installs and activates the Jurassic Ninja companion plugin on the site.
 * @param string $password System password for ssh.
 */
function add_auto_login( $password, $sysuser ) {
	$companion_api_base_url = rest_url( REST_API_NAMESPACE );
	$companion_plugin_url = COMPANION_PLUGIN_URL;
	$cmd = "wp option add auto_login 1"
		. " && wp option add jurassic_ninja_sysuser '$sysuser'"
		. " && wp option add jurassic_ninja_admin_password '$password'"
		. " && wp option add companion_api_base_url '$companion_api_base_url'"
		. " && wp plugin install --force $companion_plugin_url --activate";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates the Config Constants plugin on the site.
 */
function add_config_constants_plugin() {
	$cmd = 'wp plugin install config-constants --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates Gutenberg Plugin on the site.
 */
function add_gutenberg_plugin() {
	$cmd = 'wp plugin install gutenberg --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates Jetpack on the site.
 */
function add_jetpack() {
	$cmd = 'wp plugin install jetpack --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates Jetpack Beta Tester plugin on the site.
 */
function add_jetpack_beta_plugin() {
	$jetpack_beta_plugin_url = JETPACK_BETA_PLUGIN_URL;
	$cmd = "wp plugin install $jetpack_beta_plugin_url --activate" ;
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Activates jetpack branch in Beta plugin
 */
function activate_jetpack_branch( $branch_name ) {
	$cmd = "wp jetpack-beta branch activate $branch_name";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates WooCommerce on the site.
 */
function add_woocommerce_plugin() {
	$cmd = 'wp plugin install woocommerce --activate' ;
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates WordPress Beta Tester plugin on the site.
 */
function add_wordpress_beta_tester_plugin() {
	$cmd = 'wp plugin install wordpress-beta-tester --activate' ;
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Installs and activates the WP Log Viewer plugin on the site.
 */
function add_wp_log_viewer_plugin() {
	$cmd = 'wp plugin install wp-log-viewer --activate';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
/**
 * Downloads WordPress, creates a wp-config.php file and installs WordPress admin user.
 *
 * @param  [type] $domain            The domain name that will be configured for the site.
 * @param  [type] $wordpress_options WordPress options for the admin user and site. Resembling ServerPilot's parameter for creating an app
 * @param  [type] $dbname            The database name this WordPress will connect to.
 * @param  [type] $dbusername        The database username this WordPress will use.
 * @param  [type] $dbpassword        The database password this WordPress will use.
 */
function install_wordpress_with_cli( $domain, $wordpress_options, $dbname, $dbusername, $dbpassword ) {
	$cmd = sprintf(
		'wp core download'
		. ' && wp config create --dbname="%s" --dbuser="%s" --dbpass="%s"'
		. ' && wp core install --url="%s" --title="%s" --admin_user="%s" --admin_password="%s" --admin_email="%s"',
		$dbname,
		$dbusername,
		$dbpassword,
		$domain,
		$wordpress_options['site_title'],
		$wordpress_options['admin_user'],
		$wordpress_options['admin_password'],
		$wordpress_options['admin_email']
	);
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}
/**
 * Launches a new WordPress instance on the managed server
 * @param  String  $runtime              The PHP runtime versino to run the app on.
 * @param  Array   $features             Array of features to enable
 *         boolean config-constants      Should we add the Config Constants plugin to the site?
 *         boolean ssl                   Should we add SSL for the site?
 *         boolean gutenberg             Should we add Gutenberg to the site?
 *         boolean jetpack               Should we add Jetpack to the site?
 *         boolean jetpack-beta          Should we add Jetpack Beta Tester plugin to the site?
 *         boolean subdir_multisite      Should we enable subdir-based multisite on the site?
 *         boolean subdir_multisite      Should we enable subdomain-based multisite on the site?
 *         boolean woocommerce           Should we add WooCommerce plugin to the site?
 *         boolean wordpress-beta-tester Should we add Jetpack Beta Tester plugin to the site?
 *         boolean wp-debug-log          Should we set WP_DEBUG and WP_DEBUG log to true ?
 *         boolean wp-log-viewer         Should we add WP Log Viewer plugin to the site?
 * @param  Boolean $use_custom_launcher  Use a custom way to launch WP instead of using ServerPilot's method.
 * @return Array|Null                    null or the app data as returned by ServerPilot's API on creation.
 */
function launch_wordpress( $runtime = 'php7.0', $requested_features = [], $use_custom_launcher = false ) {
	/**
	 * Filters the array of default values for feature flags
	 *    add_filter( 'jurassic_ninja_features_default_values', function ( $features ) {
	 *       $features['myfeatures'] = false;
	 *       return $features;
	 *    } );
	 *
	 * @param array $features array of default values for feature flags
	*/
	$default_features = apply_filters( 'jurassic_ninja_features_default_values', [
		'ssl' => false,
		'config-constants' => false,
		'gutenberg' => false,
		'jetpack' => false,
		'jetpack-beta' => false,
		'subdir_multisite' => false,
		'subdomain_multisite' => false,
		'woocommerce' => false,
		'wordpress-beta-tester' => false,
		'wp-debug-log' => false,
		'wp-log-viewer' => false,
		'shortlife' => false,
		'branch' => false,
	] );
	$features = array_merge( $default_features, $requested_features );

	if ( $features['subdir_multisite'] && $features['subdomain_multisite'] ) {
		throw new \Exception( 'not-both-multisite-types', __( "Don't try to enable both types of multiste" ) );
	}

	try {
		$password = generate_random_password();
		$subdomain = '';
		$collision_attempts = 10;
		do {
			$subdomain = generate_random_subdomain();
			// Add moar randomness to shortlived sites
			if ( $features['shortlife'] ) {
				$subdomain = sprintf( "%s-%s", $subdomain, rand( 2, 500) );
			}
		} while ( subdomain_is_used( $subdomain ) && $collision_attempts-- > 0 );
			// title-case the subdomain
			// or default to the classic My WordPress Site
		$site_title = settings( 'use_subdomain_based_wordpress_title', false ) ?
			ucwords( str_replace( '-', ' ', $subdomain ) ) :
			'My WordPress Site';
		$wordpress_options = array(
			'site_title' => $site_title,
			'admin_user' => 'demo',
			'admin_password' => $password,
			'admin_email' => settings( 'default_admin_email_address' ),
		);
		$domain = sprintf( '%s.%s', $subdomain, settings( 'domain' ) );
		// If creating a subdomain based multisite, we need to tell ServerPilot that the app as a wildcard subdomain.
		$domain_arg = $features['subdomain_multisite'] ? array( $domain, '*.' . $domain ) : array( $domain );

		debug( 'Launching %s with features: %s', $domain, implode( ', ' , array_keys( array_filter( $features ) ) ) );

		debug( 'Creating sysuser for %s', $domain );

		$user = generate_new_user( $password );

		debug( 'Creating app for %s under sysuser %s', $domain, $user->data->name );

		if ( $use_custom_launcher ) {
			$app = create_sp_app( $user->data->name, $user->data->id, $runtime, $domain_arg );
			if ( is_wp_error( $app ) ) {
				throw new \Exception( 'Error creating app: ' . $app->get_error_message() );
			}
			// Reuse these credentials for the database, for now...
			$dbname = $user->data->name . '-wp-blah';
			$dbusername = $user->data->name;
			$dbpassword = $password;
			$db = create_sp_database( $app->data->id, $dbname, $dbusername, $dbpassword );
			if ( is_wp_error( $db ) ) {
				throw new \Exception( 'Error creating database for app: ' . $app->get_error_message() );
			}
			install_wordpress_with_cli( $domain_arg[0], $wordpress_options, $dbname, $dbusername, $dbpassword );
		} else {
			$app = create_sp_app( $user->data->name, $user->data->id, $runtime, $domain_arg, $wordpress_options );
		}
		log_new_site( $app->data, $features['shortlife'] );

		if ( $features['ssl'] ) {
			enable_ssl( $app->data->id );
		}
		if ( $features['jetpack'] ) {
			debug( '%s: Adding Jetpack', $domain );
			add_jetpack();
		}
		if ( $features['jetpack-beta'] ) {
			debug( '%s: Adding Jetpack Beta Tester Plugin', $domain );
			add_jetpack_beta_plugin();
		}

		if ( $features['branch'] ) {
			debug( '%s: Activating Jetpack %s branch in Beta plugin', $domain, $features["branch"]);
			activate_jetpack_branch($features['branch']);
		}

		if ( $features['wordpress-beta-tester'] ) {
			debug( '%s: Adding WordPress Beta Tester Plugin', $domain );
			add_wordpress_beta_tester_plugin();
		}

		if ( $features['wp-debug-log'] ) {
			debug( '%s: Setting WP_DEBUG_LOG and WP_DEBUG_LOG to true', $domain );
			set_wp_debug_log();
		}

		if ( $features['config-constants'] ) {
			debug( '%s: Adding Config Constants Plugin', $domain );
			add_config_constants_plugin();
		}

		if ( $features['wp-log-viewer'] ) {
			debug( '%s: Adding WP Log Viewer Plugin', $domain );
			add_wp_log_viewer_plugin();
		}

		if ( $features['gutenberg'] ) {
			debug( '%s: Adding Gutenberg', $domain );
			add_gutenberg_plugin();
		}

		if ( $features['woocommerce'] ) {
			debug( '%s: Adding WooCommerce', $domain );
			add_woocommerce_plugin();
		}
		debug( '%s: Adding Companion Plugin for Auto Login', $domain );
		add_auto_login( $password, $user->data->name );

		if ( $features['subdir_multisite'] ) {
			debug( '%s: Enabling subdir based multisite', $domain );
			enable_subdir_multisite( $domain );
		}

		if ( $features['subdomain_multisite'] ) {
			debug( '%s: Enabling subdomain based multisite', $domain );
			enable_subdomain_multisite( $domain );
		}
		/**
		 * Allow the enqueue of commands for features with each launched site.
		 * @param array $features The current feature flags
		 */
		do_action( 'jurassic_ninja_add_features', $features );

		// Runs the command via SSH
		// The commands to be run are the result of applying the `jurassic_ninja_feature_command` filter
		debug( '%s: Adding features', $domain );
		run_commands_for_features( $user->data->name, $password, $domain );
		//update_sp_sysuser( $user->data->id, null );
		debug( 'Finished launching %s', $domain );
		return $app->data;
	} catch ( \Exception $e ) {
		debug( '%s: Error [%s]: %s', $domain, $e->getCode(), $e->getMessage() );
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
 * Enables subdir-based multisite on a WordPress instance
 * @param string  $domain          The main domain for the site
 * @return [type]                   [description]
 */
function enable_subdir_multisite( $domain ) {
	$file_url = SUBDIR_MULTISITE_HTACCESS_TEMPLATE_URL;
	$email = settings( 'default_admin_email_address' );
	$cmd = "wp core multisite-install --title=\"subdir-based Network\" --url=\"$domain\" --admin_email=\"$email\" --skip-email"
		. " && cp .htaccess .htaccess-not-multisite && wget '$file_url' -O .htaccess";
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Enables subdomain-based multisite on a WordPress instance
 * @param string  $domain          The main domain for the site.
 * @return [type]                   [description]
 */
function enable_subdomain_multisite( $domain ) {
	$file_url = SUBDOMAIN_MULTISITE_HTACCESS_TEMPLATE_URL;
	$email = settings( 'default_admin_email_address' );
	// For some reason, the option auto_login gets set to a 0 after enabling multisite-install,
	// like if there were a sort of inside login happening magically.
	$cmd = "wp core multisite-install --title=\"subdomain-based Network\" --url=\"$domain\" --admin_email=\"$email\" --subdomains --skip-email"
		. " && cp .htaccess .htaccess-not-multisite && wget '$file_url' -O .htaccess"
		. ' && wp option update auto_login 1';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Returns the list of sites that are calculated to have expired
 * @return Array List of sites
 */
function expired_sites() {
	$interval = settings( 'sites_expiration', 'INTERVAL 7 DAY' );
	$interval_shortlived = settings( 'shortlived_sites_expiration', 'INTERVAL 1 HOUR' );
	return db()->get_results(
		"select * from sites where ( last_logged_in IS NOT NULL AND last_logged_in < DATE_SUB( NOW(), $interval ) )
		OR ( last_logged_in is NULL and created < DATE_SUB( NOW(), $interval ) )
		OR ( shortlived and created < DATE_SUB( NOW(), $interval_shortlived ) )",
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
	if ( db()->last_error ) {
		l( db()->last_error );
	};
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
	$return = create_sp_sysuser( $username, $password );
	if ( is_wp_error( $return ) ) {
		throw new \Exception( 'Error creating sysuser: ' . $return->get_error_message() );
	}
	return $return;
}

/**
 * Generates a random string of 12 characters.
 * @return string A string with random characters to be used as password for the WordPress administrator
 */
function generate_random_password() {
	$length = 12;
	return wp_generate_password( $length, false, false );
}

/**
 * Generates a random subdomain based on an adjective and sustantive.
 * Tries to filter out some potentially offensive combinations
 * The words come from:
 *      https://animalcorner.co.uk/animals/dung-beetle/
 *      http://grammar.yourdictionary.com/parts-of-speech/adjectives/list-of-adjective-words.html
 * Tne name is slugified.
 *
 * @return string A slugified subdomain.
 */
function generate_random_subdomain() {
	$blacklisted_words = [
		'african',
		'american',
		'asian',
		'australian',
		'black',
		'booby',
		'british',
		'chinese',
		'cuban',
		'dung',
		'erect',
		'eurasian',
		'european',
		'foolish',
		'indian',
		'italian',
		'japanese',
		'mexican',
		'peruvian',
		'southern',
		'northern',
		'sperm',
		'stupid',
		'sumatran',
		'syrian',
	];
	// Filter out some words that could lead to offensive combinations
	$max_attempts = 10;
	$regexp = implode( '|', $blacklisted_words );
	$name = 'First try';
	$i = 0;

	do {
		$generator = new CustomNameGenerator();
		$name = $generator->getName( settings( 'use_alliterations_for_subdomain', true ) );
	} while ( $i++ < $max_attempts && preg_match( "($regexp)", $name ) === 1 );

	$slug = create_slug( $name );
	return $slug;
}

/**
 * Generates a random username starting with userxxxxx
 * @return string A random username
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
function log_new_site( $data, $shortlived = false ) {
	db()->insert( 'sites',
		[
			'username' => $data->name,
			'domain' => figure_out_main_domain( $data->domains ),
			'created' => current_time( 'mysql', 1 ),
			'shortlived' => $shortlived,
		]
	);
	if ( db()->last_error ) {
		l( db()->last_error );
	};
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
		'shortlived' => $data['shortlived'],
	] );
	db()->delete( 'sites', [
		'username' => $data['username'],
		'domain' => $data['domain'],
	] );
	if ( db()->last_error ) {
		l( db()->last_error );
	};
}

/**
 * Returns all of the sites managed and created by this instance of Jurassic Ninja
 * @return Array The list of sites
 */
function managed_sites() {
	return db()->get_results( 'select * from sites', \ARRAY_A );
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
	if ( db()->last_error ) {
		l( db()->last_error );
	};
}

/**
 * Deletes the system users (and thus the site and its database)
 * for which their sites have been detected as expired, or never used.
 *
 * @return [type] [description]
 */
function purge_sites() {
	$sites = sites_to_be_purged();
	$system_users  = get_sp_sysuser_list();
	if ( is_wp_error( $system_users ) ) {
		debug( 'There was an error fetching users list for purging: (%s) - %s',
			$system_users->get_error_code(),
			$system_users->get_error_message()
		);
		return $system_users;
	}
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
		$return = delete_sp_sysuser( $user->id );
		if ( is_wp_error( $return ) ) {
			debug( 'There was an error purging site for user %s: (%s) - %s',
				$user->id,
				$return->get_error_code(),
				$return->get_error_message()
			);
		}
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
 * Runs a command on the manager server using the username and password for
 * a freshly created system user.
 * @param string $user     System user for ssh.
 * @param string $password System password for ssh.
 * @param string $cmd      The command to run on the shell
 * @return string          The command output
 */
function run_command_on_behalf( $user, $password, $cmd ) {
	$domain = settings( 'domain' );
	// Redirect all errors to stdout so exec shows them in the $output parameters
	$run = "SSHPASS=$password sshpass -e ssh -oStrictHostKeyChecking=no $user@$domain '$cmd' 2>&1";
	$output = null;
	$return_value = null;
	// Use exec instead of shell_exect so we can know if the commands failed or not
	exec( $run, $output, $return_value );
	if ( 0 !== $return_value ) {
		debug( 'Commands run finished with code %s and output: %s',
			$return_value,
			implode( " -> ", $output )
		);
		return new \WP_Error(
			'commands_did_not_run_successfully',
			"Commands didn't run OK"
		);
	}
	return null;
}

/**
 * Runs a set of commands via ssh.
 * The command string is a result of applying filter `jurassic_ninja_feature_command`
 * @param  string $user     [description]
 * @param  string $password [description]
 * @param  string $domain   [description]
 * @return [type]           [description]
 */
function run_commands_for_features( $user, $password, $domain ) {
	$wp_home = "~/apps/$user/public";
	$cmd = "cd $wp_home";
	/**
	 * Filters the string of commands that will be run on behalf of the freshly created user
	 * Use it like this, by concatenating the passed command with && and your command.
	 *
	 *    $cmd = 'wp plugin install whatever --activate';
	 *    add_filter( 'jurassic_ninja_feature_command', function ( $previous_commands ) use ( $mycmd ) {
	 *       return "$previous_commands && $mycmd";
	 *    } );
	 *
	 * @param string $cmd commands chained for running
	 */
	$filter_output = apply_filters( 'jurassic_ninja_feature_command', $cmd );
	debug( '%s: Running commands %s', $domain, $filter_output );
	$return = run_command_on_behalf( $user, $password, $filter_output );
	if ( is_wp_error( $return ) ) {
		throw new \Exception( "Commands didn't run OK" );
	}
	debug( '%s: Commands run OK', $domain );
}

function set_wp_debug_log() {
	$cmd = 'wp config --type=constant set WP_DEBUG true'
		. ' && wp config --type=constant set WP_DEBUG_LOG true'
		. ' && touch wp-content/debug.log';
	add_filter( 'jurassic_ninja_feature_command', function ( $s ) use ( $cmd ) {
		return "$s && $cmd";
	} );
}

/**
 * Calculates and returns sites that the creator has never visited.
 * @return [type] [description]
 */
function sites_never_checked_in() {
	$interval = settings( 'sites_never_checked_in_expiration', 'INTERVAL 1 HOUR' );
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
 * Checks if a subdomain is already user by a running site.
 *
 * @param $subdomain	The subdomain to check for collision with an already launched site.
 * @return bool			Return true if the domain is used by a running site.
 */
function subdomain_is_used( $subdomain ) {
	$domain = sprintf( "%s.%s", $subdomain, settings( 'domain' ) );
	$results = db()->get_results( "select * from sites where domain='$domain' limit 1", \ARRAY_A );
	return count( $results ) !== 0;
}

/**
 * Attempts to log debug messages if WP_DEBUG is on and the setting for log_debug_messages is on too.
 */
function debug() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && settings( 'log_debug_messages', false ) ) {
		error_log( call_user_func_array( 'sprintf', func_get_args() ) );
	}
}

