<?php

/*
 * Plugin Name: Jurassic Ninja
 * Plugin URI:  https://jurassic.ninja
 * Description: Launch All The Freedom
 * Version: 1.0
 * Author: Osk
 * Author URI: https://oskosk.wordpress.com
 * */

namespace jn;

require_once __DIR__ . '/lib/stuff.php';
require_once __DIR__ . '/lib/settings-stuff.php';
require_once __DIR__ . '/lib/db-stuff.php';

add_options_page();
add_scripts();
add_rest_api_endpoints();
add_cron_job();
create_tables( __FILE__ );
