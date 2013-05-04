<?php

namespace Taproot\Librarian\Listener;

use Taproot\Librarian\CrudException;
use Taproot\Librarian\CrudEvent;
use Taproot\Librarian\LibrarianInterface as Events;
use Symfony\Component\EventDispatcher;
use SplFileObject as File;

class FilesystemCrudListener implements EventDispatcher\EventSubscriberInterface {
	private $path;
	private $extension;
	private $idField = 'id';
	
	public static function getSubscribedEvents() {
		return [
			Events::PUT_EVENT => [
				['saveToFilesystem', 0], // Save the serialized data to a file
				['setEventItemId', 500] // Get the ID out from the data whilst it’s an array
			],
			Events::GET_EVENT => [
				['getFromFilesystem', 0], // Get the raw data from the file
				['setEventItemId', -500] // Once the data’s been unserialized, get the ID out
			],
			Events::DELETE_EVENT => 'deleteFromFilesystem'
		];
	}
	
	public function __construct(array $config) {
		$this->path = rtrim($config['path'], '/') . DIRECTORY_SEPARATOR;
		$this->idField = $config['idField'];
		$this->extension =ltrim($config['extension'], '.');
	}
	
	public function setEventItemId(CrudEvent $event) {
		$data = $event->getData();
		$event->setId($data[$this->idField]);
	}
	
	public function saveToFilesystem(CrudEvent $event) {
		$data = $event->getData();
		$id = $event->getId();
		
		$result = file_put_contents($this->pathForId($id), $data);
		
		if ($result !== false)
			$event->setData(true);
		else
			throw new CrudException('Couldn’t write file "' . $this->pathForId($id) . '"');
	}
	
	public function getFromFilesystem(CrudEvent $event) {
		$id = $event->getData();
		
		$data = file_get_contents($this->pathForId($id));
		
		if ($data === false)
			throw new CrudException('Couldn’t fetch file "' . $this->pathForId($id) . '"');
		else
			$event->setData($data);
	}
	
	public function deleteFromFilesystem(CrudEvent $event) {
		$id = $event->getData();
		unlink($this->pathForId($id));
	}
	
	private function pathForId($id) {
		return $this->path . $id . '.' . $this->extension;
	}
}