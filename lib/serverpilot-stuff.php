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
 * @param  String $name      The nickname of the App
 * @param  String $sysuserid The System User that will "own" this App
 * @param  String $runtime   The PHP runtime for an App. Choose from php5.4, php5.5, php5.6, php7.0, or php7.1.
 * @param  Array  $domains   An array of domains that will be used in the webserver's configuration
 * @param  Array  $wordpress An array containing the following keys: site_title , admin_user , admin_password , and admin_email
 * @return Object            An object with the new app data.
 */
function create_sp_app( $name, $sysuserid, $runtime, $domains, $wordpress = null ) {
	try {
		$app = sp()->app_create( $name, $sysuserid, $runtime, $domains, $wordpress );
		wait_for_serverpilot_action( $app->actionid );
		return $app;
	} catch ( \ServerPilotException $e ) {
		return new \WP_Error( $e->getCode(), $e->getMessage() );
	}
}

/**
 * Creates a mysql database using ServerPilot's API
 * @param  String $apid      The id of the app to attach this database.
 * @param  String $name      The name of the Database.
 * @param  String $username  The username that will be allowed to connect to this database
 * @param  String $password  The password for $username.
 * @return Object            An object with the new app data.
 */
function create_sp_database( $appid, $name, $username, $password ) {
	try {
		$app = sp()->database_create( $appid, $name, $username, $password );
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
 * Tries to enable SSL on a ServerPilot app
 * This is currently not working so well due to the amount
 * of instances created by ServerPilot and the throttling mechanism
 * enforced by Let's Encrypt.
 *
 * @param  string $app_id The ServerPilot id for the app
 * @return [type]         [description]
 */
function enable_ssl( $app_id ) {
	try {
		$data = sp()->ssl_auto( $app_id );
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
	$ok = false;
	do {
		sleep( 1 );
		$status = $sp->action_info( $action_id );
	} while ( 'open' === $status->data->status );
	return $status;
}
