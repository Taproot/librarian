<?php

namespace Taproot\Librarian\Index;

use Taproot\Librarian\Query;

abstract class AbstractQueryIndex {
	private $query;
	
	// TODO: possibly pass Librarian instance here too?
	public function __construct(Query $query) {
		$this->query = $query;
	}
	
	final public function fetch() {
		return call_user_func_args([$this->query, 'fetch'], func_get_args());
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