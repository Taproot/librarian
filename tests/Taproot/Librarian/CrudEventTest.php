<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian\CrudEvent;

class CrudEventTest extends \PHPUnit_Framework_TestCase {
	public function testCrudEventClassExists() {
		$l = new CrudEvent('test');
	}
}