<?php

namespace Taproot\Librarian\Index;

use Doctrine\DBAL;
use Taproot\Librarian\LibrarianInterface as Events;
use Taproot\Librarian\CrudEvent;

class UrlIndex extends StringIndex {
	public function getQueryIndex() {
		return new UrlQueryIndex($this, $this->db);
	}
	
	public function makeTableRepresentation(DBAL\Schema\Table $table) {
		$table->addColumn('id', 'string', ['length' => 100]);
		$table->addColumn('raw', 'string', ['length' => $this->length]);
		$table->addColumn('scheme', 'string', ['length' => 30]);
		$table->addColumn('domain', 'string', ['length' => 50]);
		$table->addColumn('port', 'integer', ['length' => 10]);
		$table->addColumn('path', 'string', ['length' => 200]);
		$table->addColumn('fragment', 'string', ['length' => 100]);
		
		$table->addColumn('last_indexed', 'integer', ['length' => 50]);
		
		$table->addIndex(['id', 'raw']);
		$table->addIndex(['id', 'domain']);
	}
	
	public function onPut(CrudEvent $event) {
		$data = $event->getData();
		
		$this->db->delete($this->getTableName(), ['id' => $event->getId()]);
		
		if (!isset($data[$this->propertyName]))
			return;
		
		$parts = parse_url($data[$this->propertyName]);
		
		$this->db->insert($this->getTableName(), [
			'id' => $event->getId(),
			'raw' => $data[$this->propertyName],
			'scheme' => @($parts['scheme'] ?: ''),
			'domain' => @($parts['host'] ?: ''),
			'port' => @($parts['port'] ?: ''),
			'path' => @($parts['path'] ?: ''),
			'fragment' => @($parts['fragment'] ?: ''),
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
		
		if (empty($data[$this->propertyName]))
			return;
		
		$now = time();
		
		$parts = parse_url($data[$this->propertyName]);
		
		$this->db->insert($this->getTableName(), [
			'id' => $id,
			'raw' => $data[$this->propertyName],
			'scheme' => @($parts['scheme'] ?: ''),
			'domain' => @($parts['host'] ?: ''),
			'port' => @($parts['port'] ?: ''),
			'path' => @($parts['path'] ?: ''),
			'fragment' => @($parts['fragment'] ?: ''),
			'last_indexed' => $now
		]);
	}
}

class UrlQueryIndex extends AbstractQueryIndex implements OrderableIndexInterface {
	public function orderBy($direction) {
		if (!in_array($direction, ['alphabetical', 'asc', 'ascending']))
			$direction = 'desc';
		else
			$direction = 'asc';
		
		$this->queryBuilder->orderBy($this->getName() . '.raw',
			$direction);
	}
	
	public function matches($match) {
		$name = $this->getName();
		
		$this->queryBuilder->andWhere($name
			. '.raw = '
			. $this->db->quote($match));
		
		return $this;
	}
	
	public function domainMatches($host) {
		$name = $this->getName();
		
		$this->queryBuilder->andWhere($name
			. '.domain = '
			. $this->db->quote($host));
		
		return $this;
	}
	
	public function hasDomain() {
		$name = $this->getName();
		
		$this->queryBuilder->andWhere($name
			. '.domain != ""');
		
		return $this;
	}
}