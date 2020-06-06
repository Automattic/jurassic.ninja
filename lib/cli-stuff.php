<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

/**
 * Comand Line Interface to Jurassic Ninja's deeds.
 */
class JN_CLI_Command extends \WP_CLI_Command {
	/**
	* Launch a spare site.
	*/
	public function launch( $args ) {
		if ( count( $args ) && $args[0] === 'spare' ) {
			\WP_CLI::line( 'Launching a spare site' );

			$app = launch_wordpress( 'default', [], true );
			if ( is_wp_error( $app ) ) {
				throw new \Exception();
			}
			\WP_CLI::line( sprintf( 'Launched spare site %s', figure_out_main_domain( $app->domains) ) );
			return;
		}
		try {
			$app = launch_wordpress( 'default', [ 'ssl' => true ] );
			if ( is_wp_error( $app ) ) {
				throw new \Exception();
			}
			\WP_CLI::line( sprintf( 'Launched %s', figure_out_main_domain( $app->domains) ) );

		} catch ( \Exception $e ) {
			\WP_CLI::line( sprintf( 'Error launching site' ) );
		}
	}

   /**
	* Run the purge job.
	*/
	public function purge( $args ) {
		try {
			$purged = purge_sites();
			if ( is_array( $purged ) && count( $purged ) ) {
				\WP_CLI::line( sprintf( 'Purged one Jurassic Ninja site.', count( $purged ) ) );
			} else {
				\WP_CLI::line( sprintf( "There weren't any sites to purge" ) );
			}

		} catch ( \Exception $e ) {
			\WP_CLI::line( sprintf( 'Error purging Jurassic Ninja sites' ) );
		}
	}

   /**
	* Refresh the spare sites pool.
	*/
	public function pool( $args ) {
		try {
			maintain_spare_sites_pool();
			\WP_CLI::line( sprintf( "Spare sites pool was refreshed" ) );
		} catch ( \Exception $e ) {
			\WP_CLI::line( sprintf( 'Error refreshing the spare sites pool' ) );
		}
	}

   /**
	* Get unhandled sysusers
	*/
	public function users( $args ) {
		try {
			$users = provisioner()->sysuser_list();
			$apps = provisioner()->get_app_list();
			$this_server_id = settings( 'serverpilot_server_id' );
			// Only check sysusers from this server.
			$users_with_apps = array_column( array_filter( $apps, function( $app ) use( $this_server_id ) {
				return $app->serverid == $this_server_id;
			} ), 'sysuserid' );

			foreach( $users as $user ) {
				\WP_CLI::line( sprintf( "%s\t%s\thttps://manage.serverpilot.io/servers/%s/users/%s",
					$user->name,
					in_array( $user->id, $users_with_apps ) ? 'Has apps' : 'Does not have any apps' ,
					settings( 'serverpilot_server_id' ),
					$user->id
				) );
			}
		} catch ( \Exception $e ) {
			\WP_CLI::line( sprintf( 'Error getting sysuser list' ) );
		}
	}

   /**
	* Get experied sites
	*/
	public function expired( $args ) {
		try {
			$sites = sites_to_be_purged();
			$this_server_id = settings( 'serverpilot_server_id' );
			// Only check sysusers from this server.
			foreach( $sites as $app ) {
				\WP_CLI::line( sprintf( "%s\t%s",
					$app['username'],
					$app['domain']
				) );
			}
		} catch ( \Exception $e ) {
			\WP_CLI::line( sprintf( 'Error getting expired sites list' ) );
		}
	}
}

\WP_CLI::add_command( 'jn', 'jn\JN_CLI_Command' );
