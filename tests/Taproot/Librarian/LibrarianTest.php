<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian as L;
use Taproot\Librarian\Index;
use DateTime;

class LibrarianTest extends \PHPUnit_Framework_TestCase {
	private $l;
	private $path;
	
	public function setUp() {
		parent::setUp();
		$this->path = __DIR__ . '/../../tmp_test_data/';
		
		$this->l = new L\Librarian('test', [
			'db' => [
				'name' => 'waterpigs_co_uk_test',
				'username' => 'test',
				'password' => 'test',
				'host' => '127.0.0.1',
				'driver' => 'pdo_mysql'
			]
		],
		['published' => new Index\DateTimeIndex('published')]);
		
		// TODO: put this setup code somewhere more abstract perhaps
		$crud = new L\Listener\FilesystemCrudListener([
			'path' => $this->path,
			'extension' => '.json',
			'idField' => 'id'
		]);
		
		$this->l->setCrudHandler($crud);
		
		$jL = new L\Listener\JsonListener();
		
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
	
	public function testBuildEnvironmentCausesPathFolderToBeCreated() {
		rmdir($this->path);
		
		$this->assertFileNotExists($this->path);
		
		$this->l->buildEnvironment();
		
		$this->assertFileExists($this->path);
	}
	
	public function testBuildEnvironmentCreatesIndexTables() {
		$db = $this->l->getConn();
		
		$db->executeQuery('DROP TABLE IF EXISTS test_datetime_index_published_on_published');
		
		$s = $db->getSchemaManager()->createSchema();
		
		$this->assertFalse($s->hasTable('test_datetime_index_published_on_published'));
		
		$queriesExecuted = $this->l->buildEnvironment();
		
		// Update schema representation
		$s = $db->getSchemaManager()->createSchema();
		
		$this->assertGreaterThanOrEqual(1, $queriesExecuted);
		$this->assertTrue($s->hasTable('test_datetime_index_published_on_published'));
		
		// Assert applying no diffs executes no queries
		$queriesExecuted = $this->l->buildEnvironment();
		$this->assertEquals(0, $queriesExecuted);
	}
	
	public function testBuildIndexesAddsRowForExistingDocument() {
		date_default_timezone_set('UTC');
		
		$this->l->put([
			'id' => 1,
			'published' => new DateTime('2013-05-09 09:42:29')
		]);
		
		$this->l->buildEnvironment();
		$this->l->buildIndexes();
		
		$db = $this->l->getConn();
		
		$rows = $db->executeQuery('select count(*) from test_datetime_index_published_on_published')->fetch();
		$this->assertEquals(1, $rows['count(*)']);
	}
}
