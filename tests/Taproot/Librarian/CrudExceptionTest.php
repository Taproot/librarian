<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian\CrudException;

class CrudExceptionTest extends \PHPUnit_Framework_TestCase {
	public function testClassExists() {
		$e = new CrudException();
	}
}