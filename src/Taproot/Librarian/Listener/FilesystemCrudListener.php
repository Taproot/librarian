<?php

namespace Taproot\Librarian\Listener;

use Taproot\Librarian\CrudEvent;
use Taproot\Librarian\LibrarianInterface as Events;
use Symfony\Component\EventDispatcher;

class FilesystemCrudListener implements EventDispatcher\EventSubscriberInterface {
	public static function getSubscribedEvents() {
		return [
			Events::PUT_EVENT => ['saveToFilesystem']
		];
	}
	
	public function saveToFilesystem(CrudEvent $event) {
		
	}
}