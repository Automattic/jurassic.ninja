<?php

namespace jn;

require_once __DIR__ . '/db-stuff.php';

/**
 * Used by the main plugin file to register a cron job
 * for purging sites.
 */
function add_cron_job() {
	if ( ! wp_next_scheduled( 'jurassic_ninja_purge' ) ) {
		wp_schedule_event( time(), 'hourly', 'jurassic_ninja_purge' );
	}

	add_action( 'jurassic_ninja_purge', 'jn\jurassic_ninja_purge' );

}

/**
 * Attempts to purge sites calculated as ready to be purged
 * @return [type] [description]
 */
function jurassic_ninja_purge() {
	purge_sites();
}
