<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian as L;

class LibrarianTest extends \PHPUnit_Framework_TestCase {
	private $l;
	
	public function setUp() {
		parent::setUp();
		$this->l = new L\Librarian('test', []);
	}
	
	public function testSavedDataCanBeRetrieved() {
		$data = [
			'id' => '1',
			'name' => 'The Test Data'
		];
		
		$this->l->put($data);
		
		try {
			$result = $this->l->get('1');
		} catch (L\CrudException $e) {
			$this->fail('Got a CrudException with message "' . $e->getMessage() . '"');
		}
		
		$this->assertEquals($data, $result);
	}
}