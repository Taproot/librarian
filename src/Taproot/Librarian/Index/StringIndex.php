<?php

namespace Taproot\Librarian\Index;

use Doctrine\DBAL;
use Taproot\Librarian\LibrarianInterface as Events;
use Taproot\Librarian\CrudEvent;

class StringIndex extends AbstractIndex {
	protected $propertyName;
	protected $length;
	
	public static function getSubscribedEvents() {
		return array_merge(parent::getSubscribedEvents(), [
			Events::PUT_EVENT => ['onPut', 100]
		]);
	}
	
	/** 
	 * @todo is 200 a sensible defaul length?
	 */
	public function __construct($propertyName, $length = 200) {
		$this->propertyName = $propertyName;
		$this->length = $length;
	}
	
	public function getQueryIndex() {
		return new StringQueryIndex($this, $this->db);
	}
	
	public function getTableName() {
		return $this->librarian->namespace . '_string_index_' . $this->name . '_on_' . $this->propertyName;
	}
	
	public function makeTableRepresentation(DBAL\Schema\Table $table) {
		$table->addColumn('id', 'string', ['length' => 30]);
		$table->addColumn('content', 'string', ['length' => $this->length]);
		$table->addColumn('last_indexed', 'integer', ['length' => 50]);
		
		$table->addIndex(['id', 'content']);
	}
	
	public function onPut(CrudEvent $event) {
		$data = $event->getData();
		
		$this->db->delete($this->getTableName(), ['id' => $event->getId()]);
		
		if (!isset($data[$this->propertyName]))
			return;
		
		$this->db->insert($this->getTableName(), [
			'id' => $event->getId(),
			'content' => $data[$this->propertyName],
			'last_indexed' => time()
		]);
	}
	
	public function update($id, $lastModified) {
		// Delete any rows for this item which were indexed before $lastModified
		assert(!empty($lastModified));
		assert(is_int($lastModified));
		
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
		
		if (empty($data[$this->propertyName]) or !is_string($data[$this->propertyName]))
			return;
		
		$now = time();
		
		$this->db->insert($this->getTableName(), [
			'id' => $id,
			'datetime' => $data[$this->propertyName],
			'last_indexed' => $now
		]);
	}
}

class StringQueryIndex extends AbstractQueryIndex implements OrderableIndexInterface {
	public function orderBy($direction) {
		if (!in_array($direction, ['alphabetical', 'asc', 'ascending']))
			$direction = 'desc';
		else
			$direction = 'asc';
		
		$this->queryBuilder->orderBy($this->db->quoteIdentifier($this->index->getName()) . '.content',
			$direction);
	}
	
	public function matches($match) {
		$name = $this->db->quoteIdentifier($this->index->getName());
		
		$this->queryBuilder->andWhere($name
			. '.content = '
			. $this->db->quote($match));
		
		return $this;
	}
}