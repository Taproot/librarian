<?php

namespace Taproot\Librarian;

use Exception;

class Query {
	private $indexes = [];
	
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