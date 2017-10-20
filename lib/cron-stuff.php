<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/db-stuff.php';
require_once __DIR__ . '/settings-stuff.php';

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
	if ( settings( 'purge_sites_when_cron_runs', true ) ) {
		purge_sites();
	}
}
