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
	function launch( $args ) {
		if ( count( $args )  ) {
			if ( $args[0] === 'spare' ) {
				\WP_CLI::line( 'Launching a spare site' );
			}
			$app = launch_wordpress( 'default', [], true );
			\WP_CLI::line( sprintf( 'Launched spare site', $app->domains[0] ) );
			return;
		}
		$app = launch_wordpress();
		\WP_CLI::line( sprintf( 'Launched %s', $app->domains[0] ) );
	}
}

\WP_CLI::add_command( 'jn', 'jn\JN_CLI_Command' );
