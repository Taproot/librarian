<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian\CrudEvent;
use Taproot\Librarian\Librarian;

class CrudEventTest extends \PHPUnit_Framework_TestCase {
	public function testCrudEventClassExists() {
		$l = new CrudEvent('test', [], new Librarian('test'));
	}
}