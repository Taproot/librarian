<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian as L;

class LibrarianTest extends \PHPUnit_Framework_TestCase {
	private $l;
	private $path;
	
	public function setUp() {
		parent::setUp();
		$this->path = realpath(__DIR__ . '/../../tmp_test_data/');
		
		$this->l = new L\Librarian('test', []);
		
		// TODO: put this setup code somewhere more abstract perhaps
		$l = new L\Listener\FilesystemCrudListener([
			'path' => $this->path,
			'extension' => '.json',
			'idField' => 'id'
		]);
		
		$jL = new L\Listener\JsonListener();
		
		$this->l->dispatcher->addSubscriber($l);
		$this->l->dispatcher->addSubscriber($jL);
	}
	
	public function testDataCanBeSaved() {
		if (file_exists($this->path . '1.json'))
			unlink($this->path . '1.json');
		
		$data = [
			'id' => '1',
			'name' => 'The Test Data'
		];
		
		try {
			$this->l->put($data);
		} catch (L\CrudException $e) {
			$this->fail('Got a CrudException with message "' . $e->getMessage() . '"');
		}
		
		return $data;
	}
	
	/**
	 * @depends testDataCanBeSaved
	 */
	public function testSavedDataCanBeRetrieved($data) {
		try {
			$result = $this->l->get('1');
			$this->assertEquals($result, $data);
			
			return $data;
		} catch (L\CrudException $e) {
			$this->fail('Got a CrudException with message "' . $e->getMessage() . '"');
		}
	}
	
	/**
	 * @depends testSavedDataCanBeRetrieved
	 */
	public function testSavedDataCanBeOverwritten($data) {
		$newData = $data;
		$newData['name'] = 'The New Name';
		$newData['content'] = 'Some Content';
		
		try {
			$this->l->put($newData);
			$result = $this->l->get('1');
			
			$this->assertEquals($newData, $result);
			$this->assertNotEquals($data, $result);
			
			return $newData;
		} catch (L\CrudException $e) {
			$this->fail('Got a CrudException with message "' . $e->getMessage() . '"');
		}
	}
	
	/**
	 * @depends testSavedDataCanBeOverwritten
	 */
	public function testSavedDataCanBeDeleted($data) {
		try {
			$this->l->delete($data['id']);
			$this->assertFalse(file_exists($this->path . '/1.json'), 'The file wasn’t deleted');
		} catch (L\CrudException $e) {
			$this->fail('Got a CrudException with message "' . $e->getMessage() . '"');
		}
	}
}
