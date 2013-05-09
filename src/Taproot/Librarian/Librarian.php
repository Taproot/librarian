<?php

namespace Taproot\Librarian;

use Doctrine\DBAL;
use Psr\Log;
use Symfony\Component\EventDispatcher;

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
	
	public function __construct($namespace, array $config = [], array $indexes = []) {
		$this->namespace = $namespace;
		
		$this->indexes = $indexes;
		
		$this->dispatcher = new EventDispatcher\EventDispatcher();
		$this->logger = new Log\NullLogger();
		
		foreach ($this->indexes as $index) {
			$index->setLibrarian($this);
			$this->dispatcher->addSubscriber($index);
		}
		
		// TODO: put this elsewhere? Currently this ties Librarian to Doctrine\DBAL
		if (isset($config['db'])) {
			$c = $config['db'];
			$config = new DBAL\Configuration();
			$connectionParams = [
				'dbname' => @($c['dbname'] ?: $c['name'] ?: $c['database'] ?: $c['db']),
				'user' => @($c['user'] ?: $c['username'] ?: null),
				'password' => @($c['password'] ?: null),
				'host' => @($c['host'] ?: null),
				'driver' => $c['driver']
			];
			
			$this->db = DBAL\DriverManager::getConnection($connectionParams, $config);
			
			foreach ($this->indexes as $index) {
				$index->setConnection($this->db);
			}
		}
	}
	
	public function getConn() {
		return $this->db;
	}
	
	public function buildEnvironment() {
		foreach ($this->indexes as $index) {
			
		}
		
		$event = new Event($this);
		$this->dispatcher->dispatch(self::BUILD_ENVIRONMENT_EVENT, $event);
	}
	
	public function buildIndexes() {
		$event = new Event($this);
		$this->dispatcher->dispatch(Event::BUILD_INDEXES, $event);
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
	
	public function query($limit = 20, $orderBy = []) {
		
	}
}