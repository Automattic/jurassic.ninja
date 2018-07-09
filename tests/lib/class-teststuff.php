<?php

require_once( dirname( __FILE__ ) . '/../../vendor/autoload.php' );

use \PHPUnit\Framework\TestCase;

define( 'ABSPATH', 'true' );
require_once( dirname( __FILE__ ) . '/../../lib/stuff.php' );


function apply_filters() {

}

function add_action() {

}

function wp_generate_password() {
	return '1231231231231';
}
/**
 * @covers Stuff
 */
final class TestStuff extends TestCase {
	public function testCanCreateSlug() {

		$this->assertEquals( 'dirty-combo', jn\create_slug( 'Dirty Combo' ) );
	}

	public function testCanGenerateRandomPassword() {

		$this->assertInternalType( 'string',jn\generate_random_password() );
		$this->assertEquals( 13, strlen( jn\generate_random_password() ) );
	}

	public function testCanFigureOutMainDomain() {
		$domains = [
			'dangerous-voldemort.jurassic.ninja',
			'*.dangerous-voldemort.jurassic.ninja',
		];
		$this->assertEquals( 'dangerous-voldemort.jurassic.ninja', jn\figure_out_main_domain( $domains ));
	}
}
