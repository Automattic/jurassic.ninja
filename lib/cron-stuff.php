<?php
/**
 * Stuff related to WP Cron.
 *
 * @package jurassic-ninja
 */

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/db-stuff.php';
require_once __DIR__ . '/settings-stuff.php';

/**
 * Used by the main plugin file to register a cron job
 * for purging sites.
 *
 * @param  string $plugin_file Nothing super useful. the plugin path.
 */
function add_cron_job( $plugin_file ) {
	// Register a new fifteen-minutes interval.
	add_filter( 'cron_schedules', 'jn\add_cron_recurrence_interval' ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected

	// Register the cron task if not there already.
	if ( ! wp_next_scheduled( 'jurassic_ninja_purge' ) ) {
		wp_schedule_event( time(), 'every_fifteen_minutes', 'jurassic_ninja_purge' );
	}

	add_action( 'jurassic_ninja_purge', 'jn\jurassic_ninja_purge_cron_task' );
	// Remove task on plugin deactivation.
	if ( wp_next_scheduled( 'jurassic_ninja_purge' ) ) {
		register_deactivation_hook( $plugin_file, 'jn\jurassic_ninja_cron_task_deactivation' );
	}

}

/**
 * Attempts to purge sites calculated as ready to be purged
 */
function jurassic_ninja_purge_cron_task() {
	if ( settings( 'purge_sites_when_cron_runs', true ) ) {
		debug( 'Running sites purge cron task for Jurassic Ninja' );
		$return = purge_sites();
		if ( is_wp_error( $return ) ) {
			debug(
				'There was an error purging sites: (%s) - %s',
				$return->get_error_code(),
				$return->get_error_message()
			);
		}
		if ( is_array( $return ) && count( $return ) ) {
			debug( 'Purged %s Jurassic Ninja site(s).', count( $return ) );
		}
	}
}

/**
 * De-registers the jurassic_ninja_purge cron task
 */
function jurassic_ninja_cron_task_deactivation() {
	wp_clear_scheduled_hook( 'jurassic_ninja_purge' );
}

/**
 * Add cron 15 minute interval
 *
 * @param array $schedules WP Cron schedule.
 *
 * @return array WP Cron.
 */
function add_cron_recurrence_interval( $schedules ) {
	$schedules['every_fifteen_minutes'] = array(
		'interval'  => 15 * \MINUTE_IN_SECONDS,
		'display'   => __( 'Every 15 Minutes', 'jurassic-ninja' ),
	);

	return $schedules;
}
