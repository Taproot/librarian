<?php

namespace Taproot\Librarian\Index;

use Doctrine\DBAL;
use Taproot\Librarian\CrudEvent;
use Taproot\Librarian\LibrarianInterface as Events;

class TaggedIndex extends AbstractIndex {
	protected $propertyName;
	
	public static function getSubscribedEvents() {
		return [
			Events::PUT_EVENT => ['updateIndex', 100]
		];
	}
	
	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
	}
	
	public function getQueryIndex() {
		return new TaggedQueryIndex($this, $this->db);
	}
	
	public function getTableName() {
		return $this->librarian->namespace . '_tagged_index_' . $this->getName() . '_on_' . $this->propertyName;
	}
	
	public function makeTableRepresentation(DBAL\Schema\Table $table) {
		$table->addColumn('id', 'string', ['length' => 20]);
		$table->addColumn('tag', 'string', ['length' => 100]);
		$table->addColumn('last_indexed', 'integer', ['length' => 50]);
		
		$table->addIndex(['id', 'tag']);
	}
	
	public function updateIndex(CrudEvent $event) {
		$data = $event->getData();
		$id = $event->getId();
		
		// Remove records currently associated with this id
		$this->db->delete($this->getTableName(), ['id' => $id]);
		
		if (empty($data[$this->propertyName]) or !is_array($data[$this->propertyName]))
			return;
		
		foreach ($data[$this->propertyName] as $tag) {
			$this->db->insert($this->getTableName(), [
				'id' => $id,
				'tag' => $tag,
				'last_indexed' => time()
			]);
		}
	}
	
	public function update($id, $lastModified) {
		
	}
}

class TaggedQueryIndex extends AbstractQueryIndex {
	// TODO: handle multiple tags in string, multiple arguments
	public function with($tag) {
		$this->queryBuilder->andWhere($this->db->quoteIdentifier($this->index->getName())
			. '.tag = '
			. $this->db->quote($tag));
		
		return $this;
	}
}