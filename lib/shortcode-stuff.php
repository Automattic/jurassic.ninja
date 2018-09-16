<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

function add_shortcodes( $file ) {
	add_action( 'init', function() use ( $file ) {
		add_shortcode( 'jurassic_ninja_launch_page', function( $atts = [] ) use ( $file ) {
			ob_start();
			$defaults = [
				'success_message' => __( 'The new WP is ready to go, visit it!', 'jurassic-ninja' ),
				'failure_message' => __( 'Oh No! There was a problem launching the new WP.', 'jurassic-ninja' ),
				'failure_image' => __( 'https://i.imgur.com/vdyaxmx.gif', 'jurassic-ninja' ),
				'spinner_message' => __( 'Launching a fresh WP with a Jetpack ...', 'jurassic-ninja' ),
			];
			$atts = shortcode_atts( $defaults, $atts, 'jurassic_ninja_launch_page' );
			include( dirname( $file ) . '/lib/views/launch-site-shortcode.php' );
			return ob_get_clean();
		} );
	} );
}

