<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

function add_shortcodes( $file ) {
	add_action( 'init', function() use ( $file ) {
		add_shortcode( 'jurassic_ninja_launch_page', function() use ( $file ) {
			ob_start();
			include( dirname( $file ) . '/lib/views/launch-site-shortcode.php' );
			return ob_get_clean();
		} );
	} );
}

