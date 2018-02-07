<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;

}
class CustomNameGenerator extends \Nubs\RandomNameGenerator\Alliteration {
	// Taken from \Nubs\RandomNameGenerator\Alliteration::_construct
	public function __construct( Randomizer $randomizer = null ) {
		$this->_randomizer = $randomizer;
		$this->_adjectives = file( __DIR__ . '/words/adjectives.txt', FILE_IGNORE_NEW_LINES );
		$this->_nouns = file( __DIR__ . '/words/nouns.txt', FILE_IGNORE_NEW_LINES );
	}
	// Based on \Nubs\RandomNameGenerator\Alliteration::getName
	public function getName( $alliteration = true ) {
		$adjective = $this->_getRandomWord( $this->_adjectives );
		$noun = $alliteration ?
			$this->_getRandomWord( $this->_nouns, $adjective[0] ) :
			$this->_getRandomWord( $this->_nouns );
		return ucwords( "{$adjective} {$noun}" );
	}
}
