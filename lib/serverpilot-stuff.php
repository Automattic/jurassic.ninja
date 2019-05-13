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
	add_action( 'jurassic_ninja_create_app', function( &$app, $username, $php_version, $domain, $wordpress_options, $features ) {
		$user = generate_new_user( $username, $wordpress_options['admin_password'] );
		// If creating a subdomain based multisite, we need to tell ServerPilot that the app as a wildcard subdomain.
		$domain_arg = ( isset( $features['subdomain_multisite'] ) && $features['subdomain_multisite'] ) ? array( $domain, '*.' . $domain ) : array( $domain );
		// Mitigate ungraceful PHP-FPM restart for shortlived sites by randomizing PHP version
		// PHP does not support graceful "restart" so every php-pool gets closed
		// each time ServerPilot needs to SIGUSR1 php for reloading configuration
		$shortlife_php_versions_alternatives = [
			'php7.3',
			'php7.2',
			'php7.0',
			'php5.6',
		];
		if ( $features['shortlife'] && 'default' === $php_version ) {
			$php_version = $shortlife_php_versions_alternatives[ array_rand( $shortlife_php_versions_alternatives ) ];
		}
		if ( ! $features['shortlife'] && 'default' === $php_version ) {
			$php_version = 'php7.3';
		}

		debug( 'Launching %s on PHP version: %s', $domain, $php_version );
		$app = create_sp_app( $user->data->name, $user->data->id, $php_version, $domain_arg, $wordpress_options );
	}, 10, 6 );

	add_action( 'jurassic_ninja_create_sysuser', function( &$return, $username, $password ) {
		try {
			$return = create_sp_sysuser( $username, $password );
		} catch ( \Exception $e ) {
			$return = new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}, 10, 3 );

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
 * @param  String $sysuserid     The System User that will "own" this App
 * @param  String $php_version   The PHP version for an App. Choose among php5.4, php5.5, php5.6, php7.0, php 7.2, or php 7.3
 * @param  Array  $domains       An array of domains that will be used in the webserver's configuration
 * @param  Array  $wordpress     An array containing the following keys: site_title , admin_user , admin_password , and admin_email
 * @return Object                An object with the new app data.
 */
function create_sp_app( $name, $sysuserid, $php_version, $domains, $wordpress ) {
	try {
		$app = sp()->app_create( $name, $sysuserid, $php_version, $domains, $wordpress );
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
		return $user;
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
		// wait_for_serverpilot_action( $data->actionid );

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

function update_sp_sysuser( $sysuserid, $password ) {
	try {
		return sp()->sysuser_update( $sysuserid, $password );
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
