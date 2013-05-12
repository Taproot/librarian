<?php

namespace Taproot\Librarian\Index;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractIndex implements EventSubscriberInterface {
	protected $db;
	protected $librarian;
	protected $name;
	
	abstract public static function getSubscribedEvents();
	
	abstract public function getQueryIndex();
	
	abstract public function getTableName();
	
	abstract public function makeTableRepresentation();
	
	public function setConnection(DBAL\Connection $conn) {
		$this->db = $conn;
	}
	
	public function setLibrarian(Librarian $l) {
		$this->librarian = $l;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getName() {
		return $this->name;
	}
}