<?php

namespace Taproot\Librarian;

use Doctrine\DBAL;
use PDO;
use Psr\Log;
use Symfony\Component\EventDispatcher;

/**
 * Librarian
 * 
 * The interface through which most of Librarian is exposed.
 * 
 * ## Example Usage:
 * 
 *     use Taproot\Librarian;
 *     $l = new Librarian\Librarian('documents', ['db' => •••], ['published' => Librarian\Index\DateTimeIndex('published')]);
 *     $l->buildEnvironment();
 *     $l->put(['id' => 1, 'published' => new DateTime(), 'content' => 'A Document!']);
 * 
 * @author Barnaby Walters
 */
class Librarian implements LibrarianInterface {
	/** @var string **/
	public $namespace;
	
	/** @var EventDispatcher\EventDispatcher **/
	public $dispatcher;
	
	/** @var Log\LoggerInterface **/
	public $logger;
	
	/** @var array IndexInterface **/
	private $indexes = [];
	
	/** @var DBAL\Connection **/
	private $db;
	
	private $crud;
	
	public function __construct($namespace, array $config = [], array $indexes = []) {
		$this->namespace = $namespace;
		
		$this->indexes = $indexes;
		
		$this->dispatcher = new EventDispatcher\EventDispatcher();
		$this->logger = new Log\NullLogger();
		
		foreach ($this->indexes as $name => $index) {
			$index->setName($name);
			$index->setLibrarian($this);
			$this->dispatcher->addSubscriber($index);
		}
		
		if (isset($config['db'])) {
			$c = $config['db'];
			
			if (!$c instanceof DBAL\Connection) {
				if ($c instanceof PDO)
					$connectionParams = ['pdo' => $c];
				else
					$connectionParams = [
						'dbname' => @($c['dbname'] ?: $c['name'] ?: $c['database'] ?: $c['db'] ?: null),
						'user' => @($c['user'] ?: $c['username'] ?: null),
						'password' => @($c['password'] ?: null),
						'host' => @($c['host'] ?: null),
						'path' => @($c['path'] ?: null),
						'driver' => @($c['driver'] ?: null),
						'driverClass' => @($c['driverClass'] ?: null)
					];
				
				$config = new DBAL\Configuration();
				$this->db = DBAL\DriverManager::getConnection($connectionParams, $config);
			} else {
				$this->db = $c;
			}
			
			foreach ($this->indexes as $index) {
				$index->setConnection($this->db);
			}
		}
	}
	
	public function setCrudHandler($crudHandler) {
		$this->crud = $crudHandler;
		$this->dispatcher->addSubscriber($this->crud);
	}
	
	public function getConn() {
		return $this->db;
	}
	
	public function buildEnvironment() {
		$event = new Event($this);
		$this->dispatcher->dispatch(self::BUILD_ENVIRONMENT_EVENT, $event);
		
		// Calculate difference between current schema and the schema we need
		$fromSchema = $this->db->getSchemaManager()->createSchema();
		$toSchema = clone $fromSchema;
		
		foreach ($this->indexes as $index) {
			$indexName = $index->getTableName();
			
			if ($toSchema->hasTable($indexName))
				$toSchema->dropTable($indexName);
			
			$table = $toSchema->createTable($indexName);
			
			$index->makeTableRepresentation($table);
		}
		
		// Execute schema diff
		$sql = $fromSchema->getMigrateToSql($toSchema, $this->db->getDatabasePlatform());
		
		assert(is_array($sql));
		
		foreach ($sql as $query) {
			$this->db->executeQuery($query);
		}
		
		return count($sql);
	}
	
	public function buildIndexes() {
		foreach ($this->crud->getAllDocumentPaths() as $id => $path) {
			$lastModified = filemtime($path);
			
			assert(is_int($lastModified));
			assert(!empty($lastModified));
			
			foreach ($this->indexes as $index) {
				$index->update($id, $lastModified);
			}
		}
		
		// TODO: will this ever do anything?
		$event = new Event($this);
		$this->dispatcher->dispatch(self::BUILD_INDEXES, $event);
	}
	
	public function clearIndexes() {
		$event = new Event($this);
		$this->dispatcher->dispatch(self::CLEAR_INDEXES, $event);
	}
	
	public function get($id) {
		return $this->dispatchCrud('get', $id, self::GET_EVENT);
	}
	
	public function put(array $item) {
		return $this->dispatchCrud('put', $item, self::PUT_EVENT);
	}
	
	public function delete($id) {
		return $this->dispatchCrud('delete', $id, self::DELETE_EVENT);
	}
	
	protected function dispatchCrud($method, $data, $eventName) {
		$event = new CrudEvent($method, $data, $this);
		$this->dispatcher->dispatch($eventName, $event);
		
		return $event->getData();
	}
	
	
	public function query($limit = 20, array $orderBy = []) {
		foreach ($this->indexes as $index) {
			$queryIndexes[$index->getName()] = $index->getQueryIndex();
		}
		
		return new Query($this, $this->db, $limit, $orderBy, $queryIndexes);
	}
}