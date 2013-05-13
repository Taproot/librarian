<?php

namespace Taproot\Librarian\Index;

use Doctrine\DBAL;
use Taproot\Librarian\CrudEvent;
use Taproot\Librarian\LibrarianInterface as Events;

/**
 * Tagged Index
 * 
 * Indexes tagged documents, allowing documents to be queried by tag.
 * 
 * @author Barnaby Walters
 */
class TaggedIndex extends AbstractIndex {
	protected $propertyName;
	
	public static function getSubscribedEvents() {
		return [
			Events::PUT_EVENT => ['onPutEvent', 100]
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
	
	public function onPutEvent(CrudEvent $event) {
		$data = $event->getData();
		$id = $event->getId();
		
		return $this->updateIndex($data, $id);
	}
	
	public function updateIndex($data, $id) {
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
	
	// TODO: figure out how to generalise most of this logic
	public function update($id, $lastModified) {
		$this->db->executeQuery('delete from ' 
			. $this->db->quoteIdentifier($this->getTableName()) 
			. ' where id = ? and last_indexed < ?',
			[$id, $lastModified]);
		
		// If there are still up-to-date rows, the index is up to date
		$res = $this->db->executeQuery('select count(*) from '
			. $this->db->quoteIdentifier($this->getTableName())
			. ' where id = ?', [$id])->fetch();
		
		if ($res['count(*)'] > 0)
			return;
		
		// The index is out of date, so load the document and update it
		$data = $this->librarian->get($id);
		
		if (empty($data[$this->propertyName]) or !$data[$this->propertyName] instanceof DateTime)
			return;
		
		$this->updateIndex($data, $id);
	}
}

class TaggedQueryIndex extends AbstractQueryIndex {
	/**
	 * Tagged With
	 * 
	 * @param string $tag
	 * @return TaggedQueryIndex $this
	 * @todo handle multiple tags in string, multiple arguments
	 */
	public function with($tag) {
		$this->queryBuilder->andWhere($this->db->quoteIdentifier($this->index->getName())
			. '.tag = '
			. $this->db->quote($tag));
		
		return $this;
	}
}