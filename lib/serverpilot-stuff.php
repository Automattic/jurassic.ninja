<?php
/**
 * Contains a bunch of wrappers to ServerPilot's API calls.
 * The thing is that ServerPilot PHP just throws if there's an error
 * so the wrappers defined here try/catch every call and return WP_Errors if The
 * library throws an exception
 *
 */

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

$serverpilot_instance = null;

add_action( 'jurassic_ninja_init', function() {
	add_action( 'jurassic_ninja_create_app', function( &$app, $php_version, $domain, $wordpress_options, $features ) {
		// If creating a subdomain based multisite, we need to tell ServerPilot that the app as a wildcard subdomain.
		$domain_arg = ( isset( $features['subdomain_multisite'] ) && $features['subdomain_multisite'] ) ? array( $domain, '*.' . $domain ) : array( $domain );
		// Mitigate ungraceful PHP-FPM restart for shortlived sites by randomizing PHP version
		// PHP does not support graceful "restart" so every php-pool gets closed
		// each time ServerPilot needs to SIGUSR1 php for reloading configuration
		$shortlife_php_versions_alternatives = [
			'php7.2',
			'php7.0',
			'php5.6',
			'php5.5',
			'php5.4',
		];
		if ( $features['shortlife'] && 'default' === $php_version ) {
			$php_version = $shortlife_php_versions_alternatives[ array_rand( $shortlife_php_versions_alternatives ) ];
		}
		if ( ! $features['shortlife'] && 'default' === $php_version ) {
			$php_version = 'php7.0';
		}

		debug( 'Creating sysuser for %s', $domain );

		$username = generate_random_username();

		$user = null;
		try {
			$user = create_sp_sysuser( $username, $wordpress_options['admin_password'] );
		} catch ( \Exception $e ) {
			$user = new \WP_Error( $e->getCode(), $e->getMessage() );
		}

		if ( is_wp_error( $user ) ) {
			throw new \Exception( 'Error creating sysuser: ' . $user->get_error_message() );
		}

		$sysuser = $user->name;
		$sysuser_id = $user->id;
		// For now, just use the generated username for naming the app.
		$app_name = $user->name;
		debug( 'Creating app for %s under sysuser %s', $domain, $sysuser );

		debug( 'Launching %s on PHP version: %s', $domain, $php_version );
		$sp_app = create_sp_app( $app_name, $sysuser_id, $php_version, $domain_arg, $wordpress_options );
		$app = [
			'name' => $app_name,
			'sysuser' => $sysuser,
			'domain' => $domain,
			'features' => $features,
			'php_version' => $php_version,
			'wordpress_options' => $wordpress_options,
			'sp_data' => $sp_app->data,
		];
	}, 10, 5 );

	add_filter( 'jurassic_ninja_sysuser_list', function( $users ) {
		$return = array_merge( $users, get_sp_sysuser_list() );
		return $return;
	} );
	add_filter( 'jurassic_ninja_delete_site', function( &$return, $user ) {
		$return = delete_sp_sysuser( $user->id );
		return $return;
	}, 10, 2 );
} );

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page', function( $options_page ) {
		$settings = [
			'title' => __( 'ServerPilot Configuration', 'jurassic-ninja' ),
			'text' => '<p>' . __( 'Configure ServerPilot client Id and Key. This need to be one of the paid plans. At least a Coach Plan', 'jurassic-ninja' ) . '</p>',
			'fields' => array(
				'serverpilot_server_id' => array(
					'id' => 'serverpilot_server_id',
					'title' => __( 'ServerPilot Server Id', 'jurassic-ninja' ),
					'text' => __( 'A ServerPilot Server Id.', 'jurassic-ninja' ),
				),
				'serverpilot_client_id' => array(
					'id' => 'serverpilot_client_id',
					'title' => __( 'ServerPilot Client Id', 'jurassic-ninja' ),
					'text' => __( 'A ServerPilot Client id.', 'jurassic-ninja' ),
				),
				'serverpilot_client_key' => array(
					'id' => 'serverpilot_client_key',
					'title' => __( 'ServerPilot Key', 'jurassic-ninja' ),
					'text' => __( 'A ServerPilot Client key.', 'jurassic-ninja' ),
				),
			),
		];
		$options_page[ SETTINGS_KEY ]['sections']['serverpilot'] = $settings;
		return $options_page;
	}, 5 );
} );

/**
 * Returns a ServerPilot instance
 * @return [type] [description]
 */
function sp() {
	global $serverpilot_instance;
	if ( ! $serverpilot_instance ) {
		try {
			$serverpilot_instance = new \ServerPilot( settings( 'serverpilot' ) );
		} catch ( \ServerPilotException $e ) {
			push_error( new \WP_error( $e->getCode(), $e->getMessage() ) );
		}
	}
	return $serverpilot_instance;
}

/**
 * Creates a PHP app using ServerPilot's API
 * @param  String $name          The nickname of the App
 * @param  String $sysuser_id    The System User that will "own" this App
 * @param  String $php_version   The PHP version for an App. Choose from php5.4, php5.5, php5.6, php7.0, or php7.1.
 * @param  Array  $domains       An array of domains that will be used in the webserver's configuration
 * @param  Array  $wordpress     An array containing the following keys: site_title , admin_user , admin_password , and admin_email
 * @return Object                An object with the new app data.
 */
function create_sp_app( $name, $sysuser_id, $php_version, $domains, $wordpress ) {
	try {
		$app = sp()->app_create( $name, $sysuser_id, $php_version, $domains, $wordpress );
		wait_for_serverpilot_action( $app->actionid );
		return $app;
	} catch ( \ServerPilotException $e ) {
		return new \WP_Error( $e->getCode(), $e->getMessage() );
	}
}

/**
 * Creates a system user using ServerPilot's API
 * @param  String $username The username
 * @param  String $password The password
 * @return Object           An object with the new user data.
 */
function create_sp_sysuser( $username, $password ) {
	try {
		$user = sp()->sysuser_create( settings( 'serverpilot_server_id' ), $username, $password );
		wait_for_serverpilot_action( $user->actionid );
		return $user->data;
	} catch ( \ServerPilotException $e ) {
		return new \WP_Error( $e->getCode(), $e->getMessage() );
	}
}

/**
 * Deletes a system user on the managed ServerPilot.
 * This deletes also all of the databases and WordPress instances of the user
 * @param  String $id The ServerPilot identifier for this user
 * @return [type]     [description]
 */
function delete_sp_sysuser( $id ) {
	try {
		return sp()->sysuser_delete( $id );
	} catch ( \ServerPilotException $e ) {
		return new \WP_Error( $e->getCode(), $e->getMessage() );
	}
}

/**
 * Tries to enable auto SSL on a ServerPilot app
 * This is currently not working so well due to the amount
 * of instances created by ServerPilot and the throttling mechanism
 * enforced by Let's Encrypt.
 *
 * @param  string $app_id The ServerPilot id for the app
 * @return [type]         [description]
 */
function enable_sp_auto_ssl( $app_id ) {
	try {
		$data = sp()->ssl_auto( $app_id );
		wait_for_serverpilot_action( $data->actionid );
		return $data;
	} catch ( \ServerPilotException $e ) {
		return new \WP_Error( $e->getCode(), $e->getMessage() );
	}
}

/**
 * Enable  SSL on a ServerPilot app according to the configured certificate
 * in Jurassic Ninja settings.
 *
 * @param  string $app_id The ServerPilot id for the app
 * @return [type]         [description]
 */
function enable_sp_ssl( $app_id ) {
	$private_key = settings( 'ssl_private_key' );
	$certificate = settings( 'ssl_certificate' );
	$ca_certificates = settings( 'ssl_ca_certificates', null );

	if ( ! $private_key || ! $certificate ) {
		return new \WP_Error( 'ssl_settings_not_present', __( 'Certificate or Private key are not configured', 'jurassic-ninja' ) );
	}

	if ( ! $ca_certificates ) {
		debug( 'No CA certificates configured in settings. This may take a little bit longer to launch' );
	}

	try {
		// Add certificate
		$data = sp()->ssl_add( $app_id, $private_key, $certificate, $ca_certificates );
		/**
		 * NOTE: Here it would make sense to wait for this action to finish.
		 * IRL: It talkes tooooo long before the action is in a success state AND
		 * without the wait, the SSL provisioning still works fine.
		 * Tested a few sites and nothing broke.
		 * Leaving it commented in case something breaks eventually.
		 */

		// Enable redirection from https to http
		$data = sp()->ssl_force( $app_id, true );
		wait_for_serverpilot_action( $data->actionid );
		return $data;
	} catch ( \ServerPilotException $e ) {
		return new \WP_Error( $e->getCode(), $e->getMessage() );
	}
}

/**
 * Returns an array of apps as reported by ServerPilot's API
 * @return Array The PHP apps that ServerPilot knows about.
 */
function get_sp_app_list() {
	try {
		return sp()->app_list()->data;
	} catch ( \ServerPilotException $e ) {
		return new \WP_Error( $e->getCode(), $e->getMessage() );
	}
}

/**
 * Returns an array of system users as reported by ServerPilot's API
 * @return Array The PHP users that ServerPilot knows about.
 */
function get_sp_sysuser_list() {
	try {
		return sp()->sysuser_list()->data;
	} catch ( \ServerPilotException $e ) {
		return new \WP_Error( $e->getCode(), $e->getMessage() );
	}
}

/**
 * Locks the process by looping until ServerPilots says the action is completed
 * @param  string $action_id The ServerPilot Id for an action
 * @return string            The status of the action
 */
function wait_for_serverpilot_action( $action_id ) {
	$sp = sp();
	do {
		sleep( 1 );
		$status = $sp->action_info( $action_id );
	} while ( 'open' === $status->data->status );
	return $status;
}
