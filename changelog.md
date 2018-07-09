
3.6.1 / 2018-07-08
==================

  * Bump to 3.6.1
  * add script composer changelog
  * Add Travis configuration
  * Fix some lint issues
  * Add linter
  * add script composer test
  * Add some basic test scaffolding
  * Add changelog file

3.6 / 2018-06-18
================

  * Add setting for setting the site admin email to the logged in user email address
  * Use the email address of the user as admin email address if the user is logged in

3.5 / 2018-05-31
================

  * Hook into the jurassic_ninja_rest_specialops_create_request_features for forcing WooCommerce when the WooCommerce Beta Tester is requested as feature from specialops
  * Add filter jurassic_ninja_rest_specialops_create_request_features
  * Add setting for enabling woocommerce-beta-tester by default
  * Add ability to launch with woocommerce-beta-tester

3.4 / 2018-05-29
================

  * The repository was moved from oskosk/jurassic.ninja to Automattic/jurassic.ninja
  * Update repo url in README
  * Update COMPANION_PLUGIN_URL

3.3.4 / 2018-04-23
==================

  * Remove some adjectives

3.3.3 / 2018-04-19
==================

  * Improve the way we handle errors for the jurassic_ninja_rest_create_request_features filter

3.3.2 / 2018-04-19
==================

  * Purge MAX_SITES at most so the purge task does not interfere with sites creation a lot

3.3.1 / 2018-04-19
==================

  * Mitigate 503 http status coming from PHP-FPM inability to due a fraceful **restart**
  * Rename $runtime to $php_version

3.3 / 2018-04-10
================

  * Add ability to launch with Code Snippets plugin
  * fix white spaces

3.2.1 / 2018-04-10
==================

  * Fix wrong name in reference to return value

3.2 / 2018-04-06
================

  * Fix case in WP Rollback
  * Add ability to launch with wp-downgrade installed
  * Add ability to launch with wp-rollback installed (#109)

3.1 / 2018-04-05
================

  * Allow to launch with gutenberg right away (#107)
  * Remove some words (#106)
  * Docs: minor fixes (#105)

3.0 / 2018-04-03
================

  * Major refactor to provide actions and filters instead of having everything done inside the launch_wordpress() function (#99)

2.1.2 / 2018-03-27
==================

  * Remove some words (#104)
  * Run the purge cron task every fifteen minutes (#102)

2.1.1 / 2018-02-18
==================

  * Define subdomain_multiste as default in rest endpoint (#97)

2.1 / 2018-02-18
================

  * Add ability to launch sites with SSL (#96)
  * Return 400 error instead of 500 if branch fails or is not there (#95)
  * Add docker config & update README (#81)
  * Fix typo in README (#90)
  * Handle WP_Error from filter (#92)
  * Add filters and actions to allow for a more modular approach for feature addition (#91)
  * Add wp-debug-log GET parameter support and setting for enabling it by default (#89)
  * Add feature wp-debug-log for launching with WP_DEBUG and WP_DEBUG_LOG set to true (#88)
  * remove some weird nouns (#87)

2.0 / 2018-02-07
================

This was a major refactor and versions weren't bumped at the time

  * Add custom random name generator (#84)
  * Add possibility to install jetpack PR branch (#74)
  * remove exception when options are not there yet (#83)
  * Use wp_generate_password (#80)
  * Add system username to options (#79)
  * Do not use code in exception when sysuser creation fails (#78)
  * Do not use code in exception when app creation fails (#77)
  * Handle errors better (#76)
  * Log user creation too (#72)
  * Add debug() function for logging messages if the setting is on (#71)
  * Default to PHP 7.0
  * Fix typo in js for wordpress-beta-tester feature
  * Wait for serverpilot action when creating user
  * Allow PHP version selection from Special Ops (#68)
  * Extend the random number generation max from 100 to 500 for shortlived sites (#67)
  * Do not add Woo by default (#66)
  * Replace debug plugin for Config Constants and WP Log Viewer (#65)
  * Use $json_params (#62)
  * Add/woocommerce get parameter (#61)
  * Add jetpack and nojetpack GET parameter support if the page including the JS carries them in the URL (#60)
  * Add more randomness to subdomain generation for shortlived sites. A random number is appended to the subdomain (#58)
  * Prevent creating ServerPilot apps with the same domain as another existing one. (#54)
  * Add ability to launch shortlived sites (#53)
  * Add setting to make launched sites have a site title related to their subdomain (#52)
  * Rename $errors to $jurassic_ninja_errors (#51)
  * Filter out some probably offensive combinations of words for the subdomains (#50)
  * Add ability to install and activate Woo (#49)
  * Add Gutenberg feature (#44)
  * Introduce managed_sites()
  * Use REST_API_NAMESPACE constant for definiin companion_api_base_Url
  * Make feature commands be in a single line
  * Make the JS only load on creating pages
  * Add docs to some functions
  * Rename table creation function
  * Rename cron task callback
  * introduce page_is_launching_page()
  * rename to add_settings_page()
  * polish main plugin file with comments and ordering stuff
  * Check for presence of ABSPATH (#34)
  * Add filter for the list of apps returned by ServerPilot (#41)
  * Check if RationalOptionPages class exists before including it (#40)
  * Add feature debug for installing the Debug plugin (#39)
  * Make launch_wordpress accept an array of features instead of multiple arguments (#38)
  * Use admin_url() instead of menu_page_url which is not present in the frontend (#37)
  * Add WordPress Beta Tester Plugin to launching options
  * say launch instead of create
  * Remove unnecessary arguments
  * Fix resolution of main domain for subdomain-based multisite when loggin its creation
  * Fix mapping of checkboxes to post data
  * Make enable_subdomain_multisite be based on the filter
  * Make enable_subdir_multisite be based on the filter
  * Use --activate option for wp instead of running install and activate
  * Make add_jetpack_beta_plugin be based on the filter
  * Make add_jetpack be based on the filter
  * Make add_auto_Login work via filters
  * Remove unused function add_companion_plugin
  * Run a single command for adding auto login
  * Run a single command for subdir-based multisite enabling
  * Run a single command for domain-based multisite enabling
  * Remove unnecessary dir template
  * Create wrapper for ServerPilot Calls
  * Add admin bar link
  * Update inline URLs for constants
  * Update column header
  * Make the main menu item be the sites admin item
  * Say permission_callback instead of options
  * Include special ops functionality (JS+PHP)
  * properly forward exception on serverpilot client instantiation as WP_Error
  * Add Special Ops endpoint only for authenticated users
  * Update menu icon
  * Check if dependencies are installed and avoid crashing on activation if they are not
  * Link to sysuser screen in serverpilots manage dashboard
  * Do not check serverpilot credentials on each page
  * rename what was called server_pilot to serverpilot
  * Say settings instead of config
  * Say settings problems instead of config errors
  * Say notices instead of errors
  * Update menu titles and slugs
  * Fix localization of strings
  * Check if ServerPilot settings allow us to do actual stuff
  * Show admin notices and block some functionality if settings are not configured
  * remove reference to master string in path (#26)
  * Fix declaration of primary key. Weird. See https://developer.wordpress.org/reference/functions/dbdelta/#comment-1329 (#25)
  * Add setting to restrict site creation to authenticated users (#24)
  * add ability to create subdomain-based multisite sites (#23)
  * Better readme again
  * Remove no longer used tests
  * Add default intervals in the code
  * Add setting for installing Jetpack Beta Tester plugin by default
  * add setting for disabling site launching
  * Call add_companion_plugin from add_auto_login instead
  * Add better installation instructions (#9)
  * remove old config file
  * show total running instances
  * use sshpass appropriately
  * add .striped to sites table
  * Add option for disabling purging on cron
  * provision api url into an option
  * remove companion plugin from this repo
  * Add option for installing Jetpack by default
  * Move some functions out of stuff.php for clarity
  * add error helpers
  * remove unnecesary access to global $wpdb
  * Better description and namings in Settings page fields
  * Add default values for some options
  * Add am/pm to dates
  * Show system username column instead of app name
  * Add checked in and last_logged_in dates
  * Better formatting and localization for created date
  * Better data in the sites table
  * consolidate calculation for expired sites and sites where the admin never logged in with credentials
  * Factor out cron callable
  * Better manage the serverpilot class instance
  * Add JSDOc to some functions
  * rename wait_for_action to wait_for_serverpilot_action
  * Sort functions by name
  * Update page headers
  * Improve styles on sites table
  * Improve plugin description
  * Improve unuseful installation isntuactions
  * Remove unnecesary dependencies now that this is a WordPress plugin
  * Remove files that are not needed anymore now that this is a WordPress plugin
  * Move cron stuff to lib file
  * Move db stuff to lib file
  * Move settings stuff to lib file
  * Use wp_safe_redirect() instead
  * Add custom page for listing launched sites
  * Use $wpdb instead of external database for logging. Create tables on plugin activation
  * Add cron job to purge sites
  * Add /checkin endpoint
  * Add /extend endpoint
  * Add -oStrictHostKeyChecking=no to ssh runner
  * move running stuff to main plugin file
  * Add basic /create endpoint
  * Add scripts
  * Add options page
  * Update db instantiation
  * Add plugin
  * Install companion plugin from github (#8)
  * Redirect to wp-admin only the first time so the redirect_to can be respected on further logins (#7)

1.0 / 2017-10-09
================

  * Introduce namespace and move intervals definition to config.inc.php (#5)
  * Update the way in which the code access the config (#4)
  * Fix most lint issues (#3)
  * Initial commit
