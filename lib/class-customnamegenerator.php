<?php
/**
 * Custom name generator.
 *
 * @package jurassic-ninja
 */

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

/**
 * Class CustomNameGenerator
 *
 * @package jurassic-ninja
 */
class CustomNameGenerator extends \Nubs\RandomNameGenerator\Alliteration {
	/**
	 * CustomNameGenerator constructor.
	 *
	 * Taken from \Nubs\RandomNameGenerator\Alliteration::_construct.
	 *
	 * @param Randomizer|null $randomizer Randomizer.
	 */
	public function __construct( Randomizer $randomizer = null ) {
		$this->_randomizer = $randomizer;
		$this->_adjectives = file( __DIR__ . '/words/adjectives.txt', FILE_IGNORE_NEW_LINES );
		$this->_nouns = file( __DIR__ . '/words/nouns.txt', FILE_IGNORE_NEW_LINES );
	}

	/**
	 * Get a random name.
	 *
	 * @param bool $alliteration Should sites start similar.
	 *
	 * @return string
	 */
	public function getName( $alliteration = true ) {
		$adjective = $this->_getRandomWord( $this->_adjectives );
		$noun = $alliteration ?
			$this->_getRandomWord( $this->_nouns, $adjective[0] ) :
			$this->_getRandomWord( $this->_nouns );
		return ucwords( "{$adjective} {$noun}" );
	}
}
