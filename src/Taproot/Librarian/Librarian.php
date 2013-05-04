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
	
	public function __construct($namespace, array $config = []) {
		$this->namespace = $namespace;
		
		$this->dispatcher = new EventDispatcher\EventDispatcher();
		$this->logger = new Log\NullLogger();
	}
	
	public function addIndexes(array $indexes) {
		
	}
	
	public function buildEnvironment() {
		
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
		
		if ($event->hasResult())
			return $event->getResult();
		else
			throw new CrudException();
	}
	
	public function query($limit = 20, $orderBy = []) {
		
	}
}