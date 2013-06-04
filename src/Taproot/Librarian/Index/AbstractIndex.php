<?php

namespace Taproot\Librarian\Index;

use BarnabyWalters\Helpers\Helpers as H;
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
			LibrarianInterface::CLEAR_INDEXES => 'onClearIndex',
			LibrarianInterface::DELETE_EVENT => 'onDelete'
		];
	}
	
	abstract public function getQueryIndex();
	
	public function getTableName() {
		return H::toAscii(strtolower($this->librarian->namespace . '_datetime_index_' . $this->name . '_on_' . $this->propertyName), ['-'], $delimeter = '_');
	}
	
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
	
	public function getName($quote = false) {
		return $quote
			? $this->db->quoteIdentifier($this->name)
			: $this->name;
	}
	
	public function onClearIndex(Event $event = null) {
		$this->db->delete($this->getTableName(), ['true']);
	}
	
	public function onDelete(Event $event) {
		$id = $event->getData();
		
		$this->db->delete($this->getTableName(), ['id' => $id]);
	}
}