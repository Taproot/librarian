<?php

namespace Taproot\Librarian;

use Doctrine\DBAL;
use Exception;

class Query {
	private $indexes = [];
	private $db;
	private $limit;
	private $queryBuilder;
	private $librarian;
	private $mainIndex;
	
	public function __construct($librarian, $db, $limit, $orderBy, $indexes) {
		$this->librarian = $librarian;
		$this->indexes = $indexes;
		$this->db = $db;
		$this->limit = $limit;
		
		$this->queryBuilder = $this->db->createQueryBuilder();
		$this->queryBuilder->setMaxResults($limit);
		
		foreach ($this->indexes as $index) {
			$index->setQuery($this);
			$index->setQueryBuilder($this->queryBuilder);
		}
		
		// TODO: make this explicitly defined?
		$this->mainIndex = current($this->indexes);
		
		$this->queryBuilder->select('distinct ' . $this->db->quoteIdentifier($this->mainIndex->getName()) . '.id')
			->from($this->mainIndex->getTableName(), $this->mainIndex->getName());
		
		foreach ($orderBy as $name => $direction) {
			$this->indexes[$name]->orderBy($direction);
		}
	}
	
	public function fetch() {
		foreach (array_slice($this->indexes, 1) as $name => $index) {
			$this->queryBuilder->leftJoin($this->mainIndex->getName(), $index->getTableName(), $name,
				$this->db->quoteIdentifier($this->mainIndex->getName())
				. '.id = '
				. $this->db->quoteIdentifier($name)
				. '.id');
		}
		
		return new DocumentCollection(array_map(function ($item) {
			return $item['id'];
		}, $this->queryBuilder->execute()->fetchAll())
		, $this->librarian);
	}
	
	public function __isset($id) {
		return isset($this->indexes[$id]);
	}
	
	public function __set($id, $value) {
		throw new Exception('Query indexes cannot be set');
	}
	
	public function __unset($id) {
		throw new Exception('Query indexes cannot be unset');
	}
	
	public function __get($id) {
		return $this->indexes[$id];
	}
}