<?php
/**
 * Implements the feature to launch sites with the user's email address.
 *
 * If the user launching a site is logged in, the site Admin email address and the admin user's Address
 * we will the one of the user launching the site.
 *
 */

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

add_action( 'jurassic_ninja_init', function() {
	add_filter( 'jurassic_ninja_wordpress_options', function( $wordpress_options ) {
		// Use the logged in user's email address as the site's email address
		if ( is_user_logged_in() && settings( 'use_user_email_as_admin_email', false ) ) {
			$user = wp_get_current_user();
			$wordpress_options['admin_email'] = $user->user_email;
		}
		return $wordpress_options;
	} );
} );

