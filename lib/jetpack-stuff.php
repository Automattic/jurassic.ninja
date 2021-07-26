<?php
/**
 * Does stuff for Jetpack.
 *
 * @package jurassic-ninja
 */

namespace jn;

// Disconnect Jetpack upon purge.
add_action(
	'jurassic_ninja_purge_site',
	function ( $site, $user ) {
		$command = "cd ~/apps/{$user->name}/public && wp jetpack disconnect blog";
		debug( '%s: Running commands %s', $user->id, $command );

		$return = run_command_on_behalf( $site['username'], $site['password'], $command );

		if ( is_wp_error( $return ) ) {
			debug(
				'There was an error disconnecting Jetpack for user %s: (%s) - %s',
				$user->id,
				$return->get_error_code(),
				$return->get_error_message()
			);
		}
	},
	10,
	2
);
