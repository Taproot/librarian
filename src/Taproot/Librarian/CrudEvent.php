<?php

namespace Taproot\Librarian;

use Symfony\Component\EventDispatcher;

class CrudEvent extends EventDispatcher\Event {
	private $method;
	private $data;
	private $id;
	private $librarian;
	
	public function __construct($method, $data, LibrarianInterface $librarian) {
		$this->method = $method;
		$this->data = $data;
		$this->librarian = $librarian;
	}
	public function getData() {
		return $this->data;
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
}