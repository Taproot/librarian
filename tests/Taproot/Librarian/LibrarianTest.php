<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian as L;

class LibrarianTest extends \PHPUnit_Framework_TestCase {
	private $l;
	private $path;
	
	public function setUp() {
		parent::setUp();
		$this->l = new L\Librarian('test', []);
		$this->path = realpath(__DIR__ . '/../../tmp_test_data/');
	}
	
	public function testSavedDataCanBeRetrieved() {
		if (file_exists($this->path . '1.json'))
			unlink($this->path . '1.json');
		
		$l = new L\Listener\FilesystemCrudListener([
			'path' => $this->path,
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