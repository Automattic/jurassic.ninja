<?php
/**
 * Implements the feature to launch sites with the user's email address.
 *
 * If the user launching a site is logged in, the site Admin email address and the admin user's Address
 * we will the one of the user launching the site.
 *
 * This feature does not provide an interface to users, just a setting to the admin.
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

add_action( 'jurassic_ninja_admin_init', function() {
	add_filter( 'jurassic_ninja_settings_options_page', function( $options_page ) {
		$field = [
			'id' => 'use_user_email_as_admin_email',
			'title' => __( 'Set the user\'s email address as the site\'s admin email address', 'jurassic-ninja' ),
			'text' => __( 'If the user launching a site is logged in, use their email address for the site', 'jurassic-ninja' ),
			'type' => 'checkbox',
			'checked' => false,
		];
		$options_page[ SETTINGS_KEY ]['sections']['domain']['fields']['use_user_email_as_admin_email'] = $field;
		return $options_page;
	}, 10 );
} );
