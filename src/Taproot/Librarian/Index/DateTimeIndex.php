<?php

namespace Taproot\Librarian\Index;

use DateTime;
use Doctrine\DBAL;
use Taproot\Librarian\Librarian;
use Taproot\Librarian\Event;
use Taproot\Librarian\CrudEvent;
use Taproot\Librarian\LibrarianInterface as Events;

class DateTimeIndex extends AbstractIndex {
	private $propertyName;
	
	public static function getSubscribedEvents() {
		return [
			// Turn the datetime property into a DateTime object after unserialisation
			Events::GET_EVENT => ['hydrateProperty', -100],
			// Turn the datetime property into a string if it’s a DateTime
			Events::PUT_EVENT => ['dehydratePropertyAndUpdateIndex', 100]
		];
	}
	
	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
	}
	
	public function getQueryIndex() {
		return new DateTimeQueryIndex($this, $this->db);
	}
	
	public function getTableName() {
		return $this->librarian->namespace . '_datetime_index_' . $this->name . '_on_' . $this->propertyName;
	}
	
	public function hydrateProperty(CrudEvent $event) {
		$data = $event->getData();
		
		if (empty($data[$this->propertyName]))
			return;
		
		try {
			$data[$this->propertyName] = new DateTime($data[$this->propertyName]);
			$event->setData($data);
		} catch (Exception $e) {
			return;
		}
	}
	
	// TODO: split functionality into two functions
	public function dehydratePropertyAndUpdateIndex(CrudEvent $event) {
		$data = $event->getData();
		
		if (empty($data[$this->propertyName]) or !$data[$this->propertyName] instanceof DateTime)
			return;
		
		// TODO: make this configurable?
		$datetime = $data[$this->propertyName];
		$data[$this->propertyName] = $datetime->format(DateTime::W3C);
		$event->setData($data);
		
		// Refresh index
		$this->db->delete($this->getTableName(), ['id' => $event->getId()]);
		$this->db->insert($this->getTableName(), [
			'id' => $event->getId(),
			'datetime' => $datetime->format('Y-m-d H:i:s'),
			'last_indexed' => time()
		]);
	}
	
	public function makeTableRepresentation(DBAL\Schema\Table $table) {
		$table->addColumn('id', 'string', ['length' => 30]);
		$table->addColumn('datetime', 'datetime');
		$table->addColumn('last_indexed', 'integer', ['length' => 50]);
		
		$table->addIndex(['id', 'datetime']);
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
		
		if (empty($data[$this->propertyName]) or !$data[$this->propertyName] instanceof DateTime)
			return;
		
		$now = time();
		
		$this->db->insert($this->getTableName(), [
			'id' => $id,
			'datetime' => $data[$this->propertyName]->format('Y-m-d H:i:s'),
			'last_indexed' => $now
		]);
	}
}

class DateTimeQueryIndex extends AbstractQueryIndex implements OrderableIndexInterface {
	public function orderBy($direction) {
		if (in_array(strtolower($direction), ['desc', 'newestfirst', 'reverse']))
			$direction = 'desc';
		else
			$direction = 'asc';
		
		$this->queryBuilder->orderBy($this->db->quoteIdentifier($this->index->getName()) . '.datetime',
			$direction);
	}
	
	public function before($datetime) {
		if (!$datetime instanceof DateTime)
			$datetime = new DateTime($datetime);
		
		$this->queryBuilder->andWhere($this->db->quoteIdentifier($this->index->getName())
			. '.datetime < "'
			. $datetime->format('Y-m-d H:i:s') . '"');
		
		return $this;
	}
	
	public function after($datetime) {
		if (!$datetime instanceof DateTime)
			$datetime = new DateTime($datetime);
		
		$tbl = $this->db->quoteIdentifier($this->index->getTableName());
		$name = $this->db->quoteIdentifier($this->index->getName());
		
		$this->queryBuilder->from('(select * from '
			. $tbl . ' as inner_datetime_table where inner_datetime_table.datetime > '
			. $this->db->quote($datetime->format('Y-m-d H:i:s'))
			. ' limit '
			. $this->queryBuilder->getMaxResults()
			. ')', $this->index->getName());
		
		return $this;
	}
	
	public function between($after, $before) {
		return $this->after($after)->before($before);
	}
}