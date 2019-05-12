<?php
/**
 * Contains a bunch of wrappers to Plest details.

 *
 */
namespace jn;
if ( ! defined( '\\ABSPATH' ) ) {
    exit;
}
add_action( 'jurassic_ninja_init', function() {
           remove_all_actions( 'jurassic_ninja_create_app', 10 );
           remove_all_actions( 'jurassic_ninja_create_sysuser', 10 );

}, 20 );
