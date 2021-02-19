<?php
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

define( 'PLUGIN_DIR', dirname( __FILE__ ) . '/../..' );
require_once dirname( __FILE__ ) . '/../../vendor/autoload.php';

define( 'ABSPATH', 'true' );

function wp_generate_password() {
	return '1231231231231';
}
/**
 * @covers Stuff
 */
final class TestStuff extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'add_shortcode' )
			->justReturn( true );

		require_once dirname( __FILE__ ) . '/../../lib/stuff.php';
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testCanCreateSlug() {

		$this->assertEquals( 'dirty-combo', jn\create_slug( 'Dirty Combo' ) );
	}

	public function testCanGenerateRandomPassword() {

		$this->assertIsString( 'string', jn\generate_random_password() );
		$this->assertEquals( 13, strlen( jn\generate_random_password() ) );
	}

	public function testCanFigureOutMainDomain() {
		$domains = array(
			'dangerous-voldemort.jurassic.ninja',
			'*.dangerous-voldemort.jurassic.ninja',
		);
		$this->assertEquals( 'dangerous-voldemort.jurassic.ninja', jn\figure_out_main_domain( $domains ) );
	}
}
