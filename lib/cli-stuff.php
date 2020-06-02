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
		if ( count( $args ) ) {
			if ( $args[0] === 'spare' ) {
				\WP_CLI::line( 'Launching a spare site' );
			}
			$app = launch_wordpress( 'default', [], true );
			\WP_CLI::line( sprintf( 'Launched spare site', $app->domains[0] ) );
			return;
		}
		try {
			$app = launch_wordpress( 'default', [ 'ssl' => true ] );
			\WP_CLI::line( sprintf( 'Launched %s', $app->domains[0] ) );

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
				\WP_CLI::line( sprintf( 'Purged %s Jurassic Ninja site(s).', count( $purged ) ) );
			} else {
				\WP_CLI::line( sprintf( "There weren't any sites to purge" ) );
			}

		} catch ( \Exception $e ) {
			\WP_CLI::line( sprintf( 'Error purging Jurassic Ninja sites' ) );
		}
	}

   /**
	* Refresh the spare sites pooll.
	*/
	public function pool( $args ) {
		try {
			maintain_spare_sites_pool();
			\WP_CLI::line( sprintf( "Spare sites pool was refreshed" ) );
		} catch ( \Exception $e ) {
			\WP_CLI::line( sprintf( 'Error refreshing the spare sites pool' ) );
		}
	}
}

\WP_CLI::add_command( 'jn', 'jn\JN_CLI_Command' );
