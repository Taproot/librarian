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
	
	}
	
	public function put(array $item) {
	
	}
	
	public function delete($id) {
	
	}
	
	public function query($limit = 20, $orderBy = []) {
	
	}
}