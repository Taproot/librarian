<?php

namespace Taproot\Librarian;

use Doctrine\DBAL;
use Exception;

class Query {
	private $indexes = [];
	private $db;
	private $limit;
	private $queryBuilder;
	
	public function __construct($db, $limit, $orderBy, $indexes) {
		$this->indexes = $indexes;
		$this->db = $db;
		$this->limit = $limit;
		
		$this->queryBuilder = $this->db->createQueryBuilder();
		$first = true;
		
		foreach ($this->indexes as $name => $index) {
			$index->setQuery($this);
			$index->setQueryBuilder($this->queryBuilder);
			
			if ($first) {
				$this->queryBuilder->select('distinct ' . $this->db->quoteIdentifier($name) . '.id')
					->from($index->getTableName(), $name);
				$first = false;
			} else {
				$this->queryBuilder->add('join',
					'left outer join '
						. $this->db->quoteIdentifier($index->getTableName())
						. ' as '
						. $this->db->quoteIdentifier($name)
						. ' using(`id`)',
					true);
			}
		}
		
		foreach ($orderBy as $name => $direction) {
			$this->indexes[$name]->orderBy($direction);
		}
	}
	
	public function fetch() {
		return $this->queryBuilder->execute()->fetchAll();
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