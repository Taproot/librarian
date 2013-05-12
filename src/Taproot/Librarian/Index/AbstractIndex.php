<?php

namespace Taproot\Librarian\Index;

use Taproot\Librarian\Librarian;
use Doctrine\DBAL;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractIndex implements EventSubscriberInterface {
	protected $db;
	protected $librarian;
	protected $name;
	
	public static function getSubscribedEvents() {
		return [];
	}
	
	abstract public function getQueryIndex();
	
	abstract public function getTableName();
	
	abstract public function makeTableRepresentation(DBAL\Schema\Table $table);
	
	abstract public function update($id, $lastModified);
	
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