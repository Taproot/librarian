<?php

namespace Taproot\Librarian\Index;

use Taproot\Librarian\Query;
use Doctrine\DBAL;

/**
 * Abstract Query Index
 * 
 * Abstract base class from which all Query Indexes inherit.
 */
abstract class AbstractQueryIndex {
	protected $query;
	protected $index;
	protected $queryBuilder;
	protected $db;
	
	/**
	 * Constructor
	 * 
	 * @param AbstractIndex $index
	 * @param DBAL\Connection $db
	 */
	public function __construct(AbstractIndex $index, DBAL\Connection $db) {
		$this->index = $index;
		$this->db = $db;
	}
	
	public function getTableName() {
		return $this->index->getTableName();
	}
	
	public function getName() {
		return $this->index->getName();
	}
	
	public function setQueryBuilder(DBAL\Query\QueryBuilder $b) {
		$this->queryBuilder = $b;
	}
	
	final public function setQuery(Query $query) {
		$this->query = $query;
	}
	
	final public function fetch() {
		return call_user_func_array([$this->query, 'fetch'], func_get_args());
	}
	
	final public function __get($id) {
		return $this->query->__get($id);
	}
	
	final public function __set($id, $value) {
		return $this->query->__set($id, $value);
	}
	
	final public function __unset($id) {
		return $this->query->__unset($id);
	}
	
	final public function __isset($id) {
		return $this->query->__isset($id);
	}
}