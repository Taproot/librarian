<?php

namespace Taproot\Librarian;

use Symfony\Component\EventDispatcher;

class CrudEvent extends EventDispatcher\Event {
	private $result = null;
	private $method;
	private $data;
	private $librarian;
	
	public function __construct($method, $data, LibrarianInterface $librarian) {
		$this->method = $method;
		$this->data = $data;
		$this->librarian = $librarian;
	}
	
	public function hasResult() {
		return !empty($this->result);
	}
	
	public function getResult() {
		return $this->result;
	}
	
	public function setResult($data) {
		$this->result = $data;
	}
}