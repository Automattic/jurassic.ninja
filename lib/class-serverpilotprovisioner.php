<?php
/**
 * Contains a bunch of wrappers to ServerPilot's API calls.
 * The thing is that ServerPilot PHP just throws if there's an error
 * so the wrappers defined here try/catch every call and return WP_Errors if The
 * library throws an exception
 *
 * @package jurassic-ninja
 */

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

/**
 * Class ServerPilotProvisioner
 *
 * @package jn
 */
class ServerPilotProvisioner {

	/**
	 * Serverpilot instance.
	 *
	 * @var \ServerPilot|null
	 */
	private $serverpilot_instance = null;

	/**
	 * Returns a ServerPilot instance
	 *
	 * @throws \Exception Throws if new ServerPilot fails.
	 */
	public function __construct() {
		if ( ! $this->serverpilot_instance ) {
			try {
				$this->serverpilot_instance = new \ServerPilot( settings( 'serverpilot' ) );
			} catch ( \ServerPilotException $e ) {
				push_error( new \WP_error( $e->getCode(), $e->getMessage() ) );
			}
		}
	}

	/**
	 * Creates an app.
	 *
	 * @param Object $user User object (WP user?).
	 * @param string $php_version PHP Version.
	 * @param string $domain Domain.
	 * @param array  $features Array of features.
	 *
	 * @return \WP_Error
	 */
	public function create_app( $user, $php_version, $domain, $features ) {
		// If creating a subdomain based multisite, we need to tell ServerPilot that the app as a wildcard subdomain.
		// Doing this for all sites doesn't hurt.
		$domain_arg = array( $domain, '*.' . $domain );
		// Mitigate ungraceful PHP-FPM restart for shortlived sites by randomizing PHP version
		// PHP does not support graceful "restart" so every php-pool gets closed
		// each time ServerPilot needs to SIGUSR1 php for reloading configuration.
		$shortlife_php_versions_alternatives = array_keys( available_php_versions() );
		if ( $features['shortlife'] && 'default' === $php_version ) {
			$php_version = sprintf( 'php%s', $shortlife_php_versions_alternatives[ array_rand( $shortlife_php_versions_alternatives ) ] );
		}
		if ( ! $features['shortlife'] && 'default' === $php_version ) {
			$php_version = sprintf( 'php%s', settings( 'default_php_version' ) );
		}

		debug( 'Launching %s on PHP version: %s', $domain, $php_version );
		try {
			$app = $this->serverpilot_instance->app_create( $user->name, $user->id, $php_version, $domain_arg, null );
			$this->wait_for_serverpilot_action( $app->actionid );
			return $app->data;
		} catch ( \ServerPilotException $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Create system user
	 *
	 * @param string $username Username.
	 * @param string $password Password.
	 */
	public function create_sysuser( $username, $password ) {
		try {
			$response = $this->serverpilot_instance->sysuser_create( settings( 'serverpilot_server_id' ), $username, $password );
			$this->wait_for_serverpilot_action( $response->actionid );
			return $response->data;
		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Creates a mysql database using ServerPilot's API
	 *
	 * @param  String $appid     The id of the app to attach this database.
	 * @param  String $name      The name of the Database.
	 * @param  String $username  The username that will be allowed to connect to this database.
	 * @param  String $password  The password for $username.
	 *
	 * @return Object            An object with the new app data.
	 */
	public function create_database( $appid, $name, $username, $password ) {
		try {
			$response = $this->serverpilot_instance->database_create( $appid, $name, $username, $password );
			$this->wait_for_serverpilot_action( $response->actionid );
			return $response->data;
		} catch ( \ServerPilotException $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Returns an array of system users as reported by ServerPilot's API
	 *
	 * @return Array The PHP users that ServerPilot knows about.
	 */
	public function get_sysuser_list() {
		try {
			return $this->serverpilot_instance->sysuser_list()->data;
		} catch ( \ServerPilotException $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Returns systems user list.
	 */
	public function sysuser_list() {
		$system_users = array();
		try {
			$system_users = $this->serverpilot_instance->sysuser_list()->data;
		} catch ( \ServerPilotException $e ) {
			$system_users = new \WP_Error( $e->getCode(), $e->getMessage() );
		}
		/**
		 * Filters the array of users listed by ServerPilot
		 *
		 * @param array $users The users returend by serverpilot
		 */
		$system_users = apply_filters( 'jurassic_ninja_sysuser_list', $system_users );
		if ( is_wp_error( $system_users ) ) {
			debug(
				'There was an error fetching users list for purging: (%s) - %s',
				$system_users->get_error_code(),
				$system_users->get_error_message()
			);
			return $system_users;
		}
		return $system_users;
	}

	/**
	 * Delete site
	 *
	 * @param string $userid User ID.
	 */
	public function delete_site( $userid ) {
		try {
			// For ServerPilot we can just delete the sysuser and it will clean
			// also its databases and sites.
			return $this->serverpilot_instance->sysuser_delete( $userid );
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
	 * @param  string $appid The ServerPilot id for the app.
	 */
	public function enable_auto_ssl( $appid ) {
		try {
			$response = $this->serverpilot_instance->ssl_auto( $appid );
			$this->wait_for_serverpilot_action( $response->actionid );
			return $response;
		} catch ( \ServerPilotException $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Enable SSL on a ServerPilot app according to the configured certificate
	 * in Jurassic Ninja settings.
	 *
	 * @param  string $appid The ServerPilot id for the app.
	 */
	public function enable_ssl( $appid ) {
		$response = $this->add_ssl_certificate( $appid );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$response = $this->force_ssl_redirection( $appid );
		return $response;
	}

	/**
	 * Add SSL certs.
	 *
	 * @param string $appid The ServerPilot id for the app.
	 */
	public function add_ssl_certificate( $appid ) {
		$private_key = settings( 'ssl_private_key' );
		$certificate = settings( 'ssl_certificate' );
		$ca_certificates = settings( 'ssl_ca_certificates', null );
		try {
			if ( ! $private_key || ! $certificate ) {
				return new \WP_Error( 'ssl_settings_not_present', __( 'Certificate or Private key are not configured', 'jurassic-ninja' ) );
			}

			if ( ! $ca_certificates ) {
				debug( 'No CA certificates configured in settings. This may take a little bit longer to launch' );
			}
			// Add certificate.
			debug( 'Adding SSL certificate for app %s', $appid );
			$response = $this->serverpilot_instance->ssl_add( $appid, $private_key, $certificate, $ca_certificates );
			/**
			 * NOTE: Here it would make sense to wait for this action to finish.
			 * IRL: It talkes tooooo long before the action is in a success state AND
			 * without the wait, the SSL provisioning still works fine.
			 * Tested a few sites and nothing broke.
			 * Leaving it commented in case something breaks eventually.
			 *
			 * $this->wait_for_serverpilot_action( $data->actionid );
			 */
			return $response;
		} catch ( \ServerPilotException $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Force SSL redirection.
	 *
	 * @param string $appid The ServerPilot id for the app.
	 */
	public function force_ssl_redirection( $appid ) {
		try {
			debug( 'Enabling forced SSL redirection for app %s', $appid );
			$response = $this->serverpilot_instance->ssl_force( $appid, true );
			return $response;
		} catch ( \ServerPilotException $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Get App info.
	 *
	 * @param string $appid The ServerPilot id for the app.
	 */
	public function get_app( $appid ) {
		try {
				return $this->serverpilot_instance->app_info( $appid )->data;
		} catch ( \ServerPilotException $e ) {
				return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Returns an array of apps as reported by ServerPilot's API
	 *
	 * @return Array The PHP apps that ServerPilot knows about.
	 */
	public function get_app_list() {
		try {
			return $this->serverpilot_instance->app_list()->data;
		} catch ( \ServerPilotException $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Update app
	 *
	 * @param string|int  $appid App ID.
	 * @param string|null $php_version PHP ver.
	 * @param null        $domains Domains.
	 */
	public function update_app( $appid, $php_version = null, $domains = null ) {
		try {
			$php_version = 'default' === $php_version ? 'php' . settings( 'default_php_version' ) : $php_version;
			$response = $this->serverpilot_instance->app_update( $appid, $php_version, $domains );
			$this->wait_for_serverpilot_action( $response->actionid );
			return $response->data;
		} catch ( \ServerPilotException $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Update the system user.
	 *
	 * @param string|int $sysuserid System user ID.
	 * @param string     $password Password.
	 *
	 * @return mixed|\WP_Error
	 */
	public function update_sysuser( $sysuserid, $password ) {
		try {
			return $this->serverpilot_instance->sysuser_update( $sysuserid, $password );
		} catch ( \ServerPilotException $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Locks the process by looping until ServerPilots says the action is completed
	 *
	 * @param  string $action_id The ServerPilot Id for an action.
	 * @return string            The status of the action
	 */
	public function wait_for_serverpilot_action( $action_id ) {
		do {
			sleep( 1 );
			$status = $this->serverpilot_instance->action_info( $action_id );
		} while ( 'open' === $status->data->status );
		return $status;
	}
}

add_action(
	'jurassic_ninja_admin_init',
	function () {
		add_filter(
			'jurassic_ninja_settings_options_page',
			function ( $options_page ) {
				$settings = array(
					'title' => __( 'ServerPilot Configuration', 'jurassic-ninja' ),
					'text' => '<p>' . __( 'Configure ServerPilot client Id and Key. This need to be one of the paid plans. At least an Economy Plan', 'jurassic-ninja' ) . '</p>',
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
				);
				$options_page[ SETTINGS_KEY ]['sections']['serverpilot'] = $settings;
				return $options_page;
			},
			5
		);
	}
);

