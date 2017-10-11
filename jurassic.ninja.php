<?php

/*
 * Plugin Name: Jurassic Ninja
 * Plugin URI:  https://github.com/oskosk/jurassic.ninja
 * Description: Launch ephemeral instances of WordPress + Jetpack using ServerPilot and an Ubuntu Box.
 * Version: 1.0
 * Author: Osk
 * Author URI: https://github.com/oskosk
 * */

namespace jn;

require_once __DIR__ . '/lib/stuff.php';
require_once __DIR__ . '/lib/settings-stuff.php';
require_once __DIR__ . '/lib/db-stuff.php';
require_once __DIR__ . '/lib/cron-stuff.php';

add_options_page();
add_scripts();
add_rest_api_endpoints();
add_cron_job();
create_tables( __FILE__ );

