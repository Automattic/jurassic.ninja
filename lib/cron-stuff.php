<?php

namespace jn;

require_once __DIR__ . '/db-stuff.php';

function add_cron_job() {
	if ( ! wp_next_scheduled( 'jurassic_ninja_purge' ) ) {
		wp_schedule_event( time(), 'hourly', 'jurassic_ninja_purge' );
	}

	add_action( 'jurassic_ninja_purge', 'jn\jurassic_ninja_purge' );

	function jurassic_ninja_purge() {
		purge_sites();
	}
}
