<?php

namespace Taproot\Librarian\Index;

use DateTime;
use Doctrine\DBAL;
use Taproot\Librarian\Librarian;
use Taproot\Librarian\Event;
use Taproot\Librarian\LibrarianInterface as Events;

// TODO: move a load of the basics to an Abstract class
class DateTimeIndex implements IndexInterface {
	private $librarian;
	private $name;
	private $propertyName;
	private $db;
	
	public static function getSubscribedEvents() {
		return [
			
		];
	}
	
	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
	}
	
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
	
	protected function getTableName() {
		return $this->librarian->namespace . '_datetime_index_' . $this->name . '_on_' . $this->propertyName;
	}
}

class DateTimeIndexQuery extends AbstractQueryIndex implements OrderableIndexInterface {
	public function orderBy($direction) {
		
	}
}