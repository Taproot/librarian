<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian\Librarian;

class LibrarianTest extends \PHPUnit_Framework_TestCase {
	public function testLibrarianClassExists() {
		$l = new Librarian('test');
	}
}