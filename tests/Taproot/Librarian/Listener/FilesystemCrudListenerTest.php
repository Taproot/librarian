<?php

namespace Taproot\Librarian\Test\Listener;

use Taproot\Librarian\Listener\FilesystemCrudListener;

class FilesystemCrudListenerTest extends \PHPUnit_Framework_TestCase {
	public function testClassExists() {
		$l = new FilesystemCrudListener();
	}
}