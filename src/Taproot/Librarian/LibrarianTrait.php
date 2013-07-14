<?php

namespace Taproot\Librarian;

/**
 * LibrarianTrait
 *
 * @author barnabywalters
 */
trait LibrarianTrait {
	/** @var LibrarianInterface */
	protected $librarian;
	
	protected function getLibrarian() {
		if ($this->librarian !== null)
			return $this->librarian;
		
		$l = new Librarian($this->librarianNamespace, [
				'db' => $this->pdo,
				'path' => $this->librarianPath,
				'type' => $this->librarianType
			],
			$this->getLibrarianIndexes()
		);
		
		$l->buildEnvironment();
		
		$this->librarian = $l;
		
		return $this->librarian;
	}
}
