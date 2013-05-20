<?php

namespace Taproot\Librarian;

use Doctrine\DBAL;
use Exception;

class Query {
	protected $indexes = [];
	
	/** @var DBAL\Connection */
	protected $db;
	
	/** @var QueryBuilder */
	public $queryBuilder;
	
	/** @var LibrarianInterface */
	protected $librarian;
	
	/** @var Index\AbstractIndex */
	protected $mainIndex;
	
	/** @var int TODO: is this actually required? */
	protected $limit;
	
	/**
	 * Constructor
	 * 
	 * @param Librarian $librarian The librarian interface responsible for the query
	 * @param DBAL\Connection $db A Doctrine Connection
	 * @param int $limit The maximum number of results to return
	 * @param array $orderBy An assoc. array of indexName => direction
	 * @param array $indexes An assoc. array of indexName => Index
	 */
	public function __construct(LibrarianInterface $librarian, DBAL\Connection $db, $limit, array $orderBy, array $indexes) {
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
		
		foreach (array_slice($this->indexes, 1) as $name => $index) {
			$this->queryBuilder->leftJoin($this->mainIndex->getName(), $index->getTableName(), $name,
				$this->db->quoteIdentifier($this->mainIndex->getName())
				. '.id = '
				. $this->db->quoteIdentifier($name)
				. '.id');
		}
		
		$this->queryBuilder->select('distinct ' . $this->db->quoteIdentifier($this->mainIndex->getName()) . '.id')
			->from($this->mainIndex->getTableName(), $this->mainIndex->getName());
		
		foreach ($orderBy as $name => $direction) {
			$this->indexes[$name]->orderBy($direction);
		}
	}
	
	public function __clone() {
		// Ensure that changes made to one Query’s QueryBuilder do not affect cloned Queries
		$this->queryBuilder = clone $this->queryBuilder;
	}
	
	public function limit($number) {
		assert('is_int($number)');
		$this->limit = $number;
		$this->queryBuilder->setMaxResults($number);
		return $this;
	}
	
	/**
	 * Fetch
	 * 
	 * @return DocumentCollection A collection of the documents returned by the query
	 */
	public function fetch() {
		$pluckIds = function ($item) {
			return $item['id'];
		};
		
		$results = $this->queryBuilder->execute()->fetchAll();
		
		$ids = array_map($pluckIds, $results);
		
		return new DocumentCollection($ids, $this->librarian);
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