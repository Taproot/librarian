<?php

namespace Taproot\Librarian;

use Exception;
use ArrayAccess, SeekableIterator, Countable;

class DocumentCollection implements ArrayAccess, SeekableIterator, Countable {
	protected $ids = [];
	protected $cache = [];
	protected $cursor = 0;
	protected $librarian;
	
	public function __construct(array $ids, Librarian $librarian) {
		// Make sure numbering of $ids is predictable
		$this->ids = array_values($ids);
		$this->librarian = $librarian;
	}
	
	public function count() {
		return count($this->ids);
	}
	
	public function getIds() {
		return $this->ids;
	}
	
	public function current() {
		return $this->offsetGet($this->cursor);
	}
	
	public function key() {
		return $this->cursor;
	}
	
	public function next() {
		$this->cursor = $this->cursor + 1;
	}
	
	public function rewind() {
		$this->cursor = 0;
	}
	
	public function seek($position) {
		assert(is_int($position));
		$this->cursor = $position;
	}
	
	public function valid() {
		return $this->offsetExists($this->cursor);
	}
	
	public function offsetGet($offset) {
		if (!isset($this->cache[$offset]))
			$this->cache[$offset] = $this->librarian->get($this->ids[$offset]);
		
		return $this->cache[$offset];
	}
	
	public function offsetExists($offset) {
		return isset($this->ids[$offset]);
	}
	
	public function offsetSet($offset, $value) {
		$this->offsetUnset($offset);
	}
	
	public function offsetUnset($offset) {
		throw new Exception('DocumentCollection items cannot be set');
	}
}