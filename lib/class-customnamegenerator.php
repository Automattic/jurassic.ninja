<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;

}
class CustomNameGenerator extends \Nubs\RandomNameGenerator\Alliteration {
	public function getName() {
		$adjective = $this->_getRandomWord( $this->_adjectives );
		$noun = $this->_getRandomWord( $this->_nouns );
		return ucwords( "{$adjective} {$noun}" );
	}
}
