<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

function add_shortcodes( $file ) {
	add_action( 'init', function() use ( $file ) {
		add_shortcode( 'jurassic_ninja_launch_page', function() use ( $file ) {
			$ret = include( dirname( $file ) . '/lib/views/launch-site-shortcode.php' );
			return $ret;
		} );
	} );
}

