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
		$l = new L\Listener\FilesystemCrudListener([
			'path' => realpath(__DIR__ . '/../../tmp_test_data/'),
			'extension' => '.json',
			'idField' => 'id'
		]);
		
		$jL = new L\Listener\JsonListener();
		
		$this->l->dispatcher->addSubscriber($l);
		$this->l->dispatcher->addSubscriber($jL);
		
		$data = [
			'id' => '1',
			'name' => 'The Test Data'
		];
		
		try {
			$this->l->put($data);
			$result = $this->l->get('1');
		} catch (L\CrudException $e) {
			$this->fail('Got a CrudException with message "' . $e->getMessage() . '"');
		}
		
		$this->assertEquals($data, $result);
	}
}