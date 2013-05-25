<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian as L;
use Taproot\Librarian\Index;
use Doctrine\DBAL;
use DateTime;

/**
 * Librarian Test Suite
 * 
 * Mainly high-level integration/acceptance tests in here.
 * 
 * @todo add tear down functions for clearing the environment
 * @todo split this up into different test suites
 * @author Barnaby Walters
 */
class LibrarianTest extends \PHPUnit_Framework_TestCase {
	private $l;
	private $crud;
	private $path;
	
	public static function setUpBeforeClass() {
		date_default_timezone_set('UTC');
	}
	
	public function clearEnvironment() {
		foreach ($this->crud->getAllDocumentPaths() as $path) {
			unlink($path);
		}
		
		$this->l->clearIndexes();
	}
	
	public function setUp() {
		parent::setUp();
		$this->path = realpath(__DIR__ . '/../../') . '/tmp_test_data/';
		
		$this->l = new L\Librarian('test', [
			'db' => [
				'name' => 'waterpigs_co_uk_test',
				'username' => 'test',
				'password' => 'test',
				'host' => '127.0.0.1',
				'driver' => 'pdo_mysql'
			],
			'path' => $this->path,
			'type' => 'json'
		],
		[
			'published' => new Index\DateTimeIndex('published'),
			'tagged' => new Index\TaggedIndex('tags')
		]);
		
		$this->crud = $this->l->getCrudHandler();
	}
	
	public function testDataCanBeSaved() {
		$this->clearEnvironment();
		
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
		$this->clearEnvironment();
		
		rmdir($this->path);
		
		$this->assertFileNotExists($this->path);
		
		$this->l->buildEnvironment();
		
		$this->assertFileExists($this->path);
	}
	
	public function testBuildEnvironmentCreatesIndexTables() {
		$this->clearEnvironment();
		
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
	
	// TODO: split this up into multiple tests
	public function testBuildIndexesAddsRowForExistingDocument() {
		$this->clearEnvironment();
		
		$this->l->put([
			'id' => 1,
			'published' => new DateTime('2013-05-09 09:42:29')
		]);
		
		$this->l->buildEnvironment();
		$this->l->buildIndexes();
		
		$db = $this->l->getConn();
		
		$rows = $db->executeQuery('select count(*), last_indexed from test_datetime_index_published_on_published')->fetch();
		$this->assertEquals(1, $rows['count(*)']);
		$lastIndexed = $rows['last_indexed'];
		
		sleep(1);
		
		// Assert doesn’t change the row if the document hasn’t changed
		$this->l->buildIndexes();
		
		$rows = $db->executeQuery('select last_indexed from test_datetime_index_published_on_published')->fetch();
		$this->assertEquals($lastIndexed, $rows['last_indexed']);
		
		// Assert does change the row once the document *has* changed
		$this->l->put([
			'id' => 1,
			'published' => new DateTime('2013-05-09 09:42:30'),
			'newField' => 'nothing to see here'
		]);
		
		$rows = $db->executeQuery('select last_indexed from test_datetime_index_published_on_published where id = "1"')->fetch();
		$this->assertNotEquals($lastIndexed, $rows['last_indexed']);
	}
	
	public function testDateTimeQueryOrderBy() {
		$this->clearEnvironment();
		
		$this->l->put([
			'id' => 1,
			'published' => new DateTime('2013-05-01 12:00:00')
		]);
		
		$this->l->put([
			'id' => 2,
			'published' => new DateTime('2013-05-05 12:00:00')
		]);
		
		$this->l->put([
			'id' => 3,
			'published' => new DateTime('2013-05-03 12:00:00')
		]);
		
		$docs = $this->l->query(20, $orderBy = ['published' => 'newestFirst'])->fetch();
		
		$this->assertEquals([2, 3, 1], $docs->getIds());
		
		$doc = $docs[0];
		
		$this->assertEquals($doc['id'], 2);
	}
	
	public function testDateTimeBeforeAfterPagination() {
		$this->clearEnvironment();
		
		$this->l->put([
			'id' => 1,
			'published' => new DateTime('2013-05-01 12:00:00')
		]);
		
		$this->l->put([
			'id' => 2,
			'published' => new DateTime('2013-05-03 12:00:00')
		]);
		
		$this->l->put([
			'id' => 3,
			'published' => new DateTime('2013-05-05 12:00:00')
		]);
		
		$this->l->put([
			'id' => 4,
			'published' => new DateTime('2013-05-06 12:00:00')
		]);
		
		$docs = $this->l->query(2, $orderBy = ['published' => 'newestFirst'])
			->published->before('2013-05-06 13:00:00')
			->fetch();
		
		$this->assertEquals([4, 3], $docs->getIds());
		
		$docs = $this->l->query(2, $orderBy = ['published' => 'oldestFirst'])
			->published->after('2013-05-02 12:00:00')
			->fetch();
		
		$this->assertEquals([2, 3], $docs->getIds());
	}
	
	public function testTaggedIndexJoinsCorrectly() {
		$this->clearEnvironment();
		
		$this->l->put([
			'id' => 1,
			'published' => '2013-05-01 12:00:00',
			'tags' => ['personal', 'web']
		]);
		
		$this->l->put([
			'id' => 2,
			'published' => '2013-05-02 12:00:00',
			'tags' => ['food', 'web']
		]);
		
		$this->l->put([
			'id' => 3,
			'published' => '2013-05-03 12:00:00',
			'tags' => ['thing', 'another']
		]);
		
		$this->l->put([
			'id' => 4,
			'published' => '2013-05-04 12:00:00',
			'tags' => ['web']
		]);
		
		$docs = $this->l->query(2, $orderBy = ['published' => 'newestFirst'])
			->tagged->with('web')
			->fetch();
		
		$this->assertEquals([4, 2], $docs->getIds());
		
		$docs = $this->l->query(2, $orderBy = ['published' => 'oldestFirst'])
			->published->after('2013-05-01 15:00:00')
			->tagged->with('web')
			->fetch();
		
		$this->assertEquals([2, 4], $docs->getIds());
	}
	
	public function testDoctrineQueryBuilderHandlesJoinsCorrectly() {
		$this->clearEnvironment();
		
		$db = DBAL\DriverManager::getConnection([
			'name' => 'waterpigs_co_uk_test',
			'username' => 'test',
			'password' => 'test',
			'host' => '127.0.0.1',
			'driver' => 'pdo_mysql'
		], new DBAL\Configuration);
		
		$qb = $db->createQueryBuilder();
		
		$qb->where('t.thing = "value"');
		
		$qb->from('tablename', 't');
		
		$qb->select('*');
		$qb->leftJoin('t', 'othertable', 'o', 'o.id = t.id');
		
		// Turns out resetQueryPart must be called before calling from() again or
		// joins will be completely discarded
		$qb->resetQueryPart('from');
		$qb->from('(select * from inner_table)', 't');
		
		$this->assertEquals('SELECT * FROM (select * from inner_table) t LEFT JOIN othertable o ON o.id = t.id WHERE t.thing = "value"',
			$qb->getSql());
	}
	
	public function testDocumentCollectionReverse() {
		$this->clearEnvironment();
		
		$this->l->put([
			'id' => 1,
			'published' => '2013-05-01 12:00:00'
		]);
		
		$this->l->put([
			'id' => 2,
			'published' => '2013-05-02 12:00:00'
		]);
		
		$docs = $this->l->query(2, $orderBy = ['published' => 'oldestFirst'])->fetch();
		$this->assertEquals([1, 2], $docs->getIds());
		
		$docs->reverse();
		$this->assertEquals([2, 1], $docs->getIds());
	}
	
	public function testDocumentCollectionCountIsZeroForEmptyQueries() {
		$this->clearEnvironment();
		
		$this->l->put([
			'id' => 1,
			'published' => '2013-05-01 12:00:00'
		]);
		
		$matches = $this->l->query(1, $orderBy = ['published' => 'newestFirst'])->fetch();
		
		$earliestDateTime = $matches->last()['published'];
		
		$matches = $this->l->query(1, $orderBy = ['published' => 'newestFirst'])
			->published->before($earliestDateTime)
			->fetch();
		
		$this->assertEquals(0, count($matches));
	}
	
	public function testFilteredPaginationWorkflow() {
		$this->clearEnvironment();
		
		foreach (range(1, 10) as $item) {
			$this->l->put([
				'id' => $item,
				'published' => '2013-05-' . str_pad($item, 2, '0', STR_PAD_LEFT) . ' 12:00:00',
				'tags' => $item % 2 == 0 ? ['web'] : ['personal']
			]);
		}
		
		$shownQuery = $this->l->query(2, $orderBy = ['published' => 'newestFirst']);
		$beforeQuery = $this->l->query(1, $orderBy = ['published' => 'newestFirst']);		
		$afterQuery = $this->l->query(1, $orderBy = ['published' => 'oldestFirst']);
		
		array_map(function ($query) {
			$query->tagged->with('web');
		}, [$shownQuery, $beforeQuery, $afterQuery]);
		
		$shown = $shownQuery->published->before('2013-05-07')->fetch();
		
		$this->assertEquals([6, 4], $shown->getIds());
		
		$earlierResults = $beforeQuery->published->before($shown->last()['published'])->fetch();
		$laterResults = $afterQuery->published->after($shown->first()['published'])->fetch();
		
		$this->assertEquals([2], $earlierResults->getIds());
		$this->assertEquals([8], $laterResults->getIds());
	}
}
