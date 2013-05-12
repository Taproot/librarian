<?php

namespace Taproot\Librarian\Index;

use Doctrine\DBAL;

class TaggedIndex extends AbstractIndex {
	protected $propertyName;
	
	public static function getSubscribedEvents() {
		return [];
	}
	
	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
	}
	
	public function getQueryIndex() {
		return new TaggedQueryIndex($this, $this->db);
	}
	
	public function getTableName() {
		return $this->librarian->getNamespace() . '_tagged_index_' . $this->getName() . '_on_' . $this->propertyName;
	}
	
	public function makeTableRepresentation(DBAL\Schema\Table $table) {
		$table->addColumn('id', 'string', ['length' => 20]);
		$table->addColumn('tag', 'string', ['length' => 100]);
		$table->addColumn('last_indexed', 'integer', ['length' => 50]);
		
		$table->addIndex(['id', 'tag']);
	}
}

class TaggedQueryIndex extends AbstractQueryIndex {
	protected $index;
	
	public function __construct(TaggedIndex $index) {
		$this->index = $index;
	}
	
	public function with($tag) {
		
	}
}