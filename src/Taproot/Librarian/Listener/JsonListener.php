<?php

namespace Taproot\Librarian\Listener;

use Symfony\Component\EventDispatcher;
use Taproot\Librarian\LibrarianInterface as Events;
use Taproot\Librarian as L;

/**
 * JSON Listener
 * 
 * Listens for put and get events, encodes/decodes array <==> JSON
 * 
 * @todo perhaps generalise into EncodingListenerInterface
 */
class JsonListener implements EventDispatcher\EventSubscriberInterface {
	public static function getSubscribedEvents() {
		return [
			Events::PUT_EVENT => ['encodeJson', 10], // Encode the array as JSON before saving
			Events::GET_EVENT => ['decodeJson', -10] // Decode the array from JSON before returning
		];
	}
	
	public function encodeJson(L\CrudEvent $event) {
		$data = $event->getData();
		$json = json_encode($data, JSON_PRETTY_PRINT);
		$event->setData($json);
	}
	
	public function decodeJson(L\CrudEvent $event) {
		$json = $event->getData();
		$data = json_decode($json, true);
		$event->setData($data);
	}
}