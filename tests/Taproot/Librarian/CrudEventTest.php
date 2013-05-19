<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian\CrudEvent;
use Taproot\Librarian\Librarian;

class CrudEventTest extends \PHPUnit_Framework_TestCase {
	public function testCrudEventShouldHaveProperties() {
		$l = new Librarian('test');
		$data = [1, 2, 3, 4];
		$method = 'test';
		$e = new CrudEvent($method, $data, $l);
		
		$this->assertEquals($e->getData(), $data);
	}
	
	public function testProperties() {
		$l = new Librarian('test');
		$e = new CrudEvent('test', [], $l);
		
		$e->setData([1, 2, 3]);
		$this->assertEquals($e->getData(), [1, 2, 3]);
		
		$e->setId(1);
		$this->assertEquals($e->getId(), 1);
	}
}