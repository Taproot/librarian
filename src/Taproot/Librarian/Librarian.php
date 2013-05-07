<?php

namespace Taproot\Librarian;

use Symfony\Component\EventDispatcher;
use Psr\Log;

class Librarian implements LibrarianInterface {
	/** @var string **/
	public $namespace;
	
	/** @var EventDispatcher\EventDispatcher **/
	public $dispatcher;
	
	/** @var Log\LoggerInterface **/
	public $logger;
	
	/** @var array IndexInterface **/
	private $indexes = [];
	
	public function __construct($namespace, array $config = [], array $indexes = []) {
		$this->namespace = $namespace;
		
		$this->indexes = $indexes;
		
		$this->dispatcher = new EventDispatcher\EventDispatcher();
		$this->logger = new Log\NullLogger();
	}
	
	public function buildEnvironment() {
		$event = new Event($this);
		$this->dispatcher->dispatch(self::BUILD_ENVIRONMENT_EVENT, $event);
	}
	
	public function buildIndexes() {
		
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