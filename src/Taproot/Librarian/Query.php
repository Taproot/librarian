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
	
	public function __construct($librarian, $db, $limit, $orderBy, $indexes) {
		$this->librarian = $librarian;
		$this->indexes = $indexes;
		$this->db = $db;
		$this->limit = $limit;
		
		$this->queryBuilder = $this->db->createQueryBuilder();
		$this->queryBuilder->setMaxResults($limit);
		$first = true;
		
		foreach ($this->indexes as $name => $index) {
			$index->setQuery($this);
			$index->setQueryBuilder($this->queryBuilder);
			
			// TODO: determine which tables to join lazily if there’s a performance benefit;
			if ($first) {
				$this->queryBuilder->select('distinct ' . $this->db->quoteIdentifier($name) . '.id')
					->from($index->getTableName(), $name);
				$first = false;
				$mainIndexId = $name;
			} else {
				$this->queryBuilder->leftJoin($mainIndexId, $index->getTableName(), $name,
					$this->db->quoteIdentifier($mainIndexId)
					. '.id = '
					. $this->db->quoteIdentifier($name)
					. '.id');
			}
		}
		
		foreach ($orderBy as $name => $direction) {
			$this->indexes[$name]->orderBy($direction);
		}
	}
	
	public function fetch() {
		//echo $this->queryBuilder->getSql() . "\n";
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