<?php

namespace Taproot\Librarian;

use Exception;
use ArrayAccess, SeekableIterator, Countable, RuntimeException;

/**
 * Document Collection
 * 
 * The object returned from Query::fetch(). Wraps an array of document IDs,
 * lazily fetches them when they’re requested, then caches them locally.
 * 
 * @author Barnaby Walters
 */
class DocumentCollection implements ArrayAccess, SeekableIterator, Countable {
	protected $ids = [];
	protected $cache = [];
	protected $cursor = 0;
	protected $getter;
	
	/**
	 * Constructor
	 * 
	 * @todo maybe accept a callable for the second argument, to ease testing and reuse?
	 */
	public function __construct(array $ids, $getter) {
		// Make sure numbering of $ids is predictable
		$this->ids = array_values($ids);
		
		if ($getter instanceof LibrarianInterface)
			$this->getter = [$getter, 'get'];
		elseif (is_callable($getter))
			$this->getter = $getter;
		else
			return new RuntimeException('The second argument must a callable or an instance of LibrarianInterface');
	}
	
	public function count() {
		return count($this->ids);
	}
	
	/**
	 * Get IDs
	 * 
	 * @return array An array of the IDs of documents in this collection
	 */
	public function getIds() {
		return $this->ids;
	}
	
	/**
	 * Reverse
	 * 
	 * Reverses the order of documents in the collection, resets the cursor
	 * 
	 * @return DocumentCollection $this
	 */
	public function reverse() {
		$this->ids = array_reverse($this->ids);
		$this->cursor = 0;
		return $this;
	}
	
	public function first() {
		return $this->offsetGet(0);
	}
	
	public function last() {
		return $this->offsetGet(count($this->ids) - 1);
	}
	
	public function shuffle() {
		$oldIds = $this->ids;
		shuffle($this->ids);
		
		if ($oldIds == $this->ids)
			$this->shuffle();
		
		return $this;
	}
	
	public function filterIds($function)  {
		assert('is_callable($function)');
		
		$this->ids = array_values(array_filter($this->ids, $function));
		
		return $this;
	}
	
	public function filter($function) {
		assert('is_callable($function)');
		$getter = [$this, 'offsetGet'];
		
		$filterable = array_map(function($id, $key) {
			return [$key, $id];
		}, $this->ids, array_keys($this->ids));
		
		$filtered = array_values(array_filter($filterable, function ($f) use ($function, $getter) {
			return $function($getter($f[0]));
		}));
		
		$this->ids = array_map(function ($f) { return $f[1]; }, $filtered);
		
		return $this;
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
		if (!$this->offsetExists($offset))
			return null;
		
		$getter = $this->getter;
		
		if (!isset($this->cache[$offset]))
			$this->cache[$offset] = $getter($this->ids[$offset]);
		
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