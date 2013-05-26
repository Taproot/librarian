<?php

namespace Taproot\Librarian\Index;

use Taproot\Librarian\Event;
use Taproot\Librarian\LibrarianInterface;
use Doctrine\DBAL;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Abstract Index
 * 
 * Abstract base class for Indexes. Defines a bunch of stuff which must be implemented,
 * some generic getter/setters
 * 
 * @todo make updating a record on save a subscribed event and abstract method by default
 * 
 * @author Barnaby Walters
 */
abstract class AbstractIndex implements EventSubscriberInterface {
	protected $db;
	protected $librarian;
	protected $name;
	
	public static function getSubscribedEvents() {
		return [
			LibrarianInterface::CLEAR_INDEXES => 'clearIndex'
		];
	}
	
	abstract public function getQueryIndex();
	
	abstract public function getTableName();
	
	abstract public function makeTableRepresentation(DBAL\Schema\Table $table);
	
	abstract public function update($id, $lastModified);
	
	public function setConnection(DBAL\Connection $conn) {
		$this->db = $conn;
	}
	
	public function setLibrarian(LibrarianInterface $l) {
		$this->librarian = $l;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function clearIndex(Event $event = null) {
		$this->db->delete($this->getTableName(), ['true']);
	}
}