<?php

namespace Taproot\Librarian;

use Symfony\Component\EventDispatcher\Event as BaseEvent;

class Event extends BaseEvent {
	private $librarian;
	
	public function __construct(Librarian $librarian) {
		$this->librarian = $librarian;
	}
	
	public function getLibrarian() {
		return $this->librarian;
	}
	
	public function setLibrarian(Librarian $l) {
		$this->librarian = $l;
	}
}