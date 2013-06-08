<?php

namespace Taproot\Librarian\Listener;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\EventDispatcher;
use Taproot\Librarian\LibrarianInterface as Events;
use Taproot\Librarian as L;

/**
 * YAML Listener
 * 
 * Listens for put and get events, encodes/decodes array <==> YAML
 * 
 * @author Barnaby Walters
 */
class SymfonyYamlListener implements EventDispatcher\EventSubscriberInterface {
	public $serialise;
	
	public static function getSubscribedEvents() {
		return [
			Events::PUT_EVENT => ['encode', 10], // Encode the array as JSON before saving
			Events::GET_EVENT => ['decode', -10] // Decode the array from JSON before returning
		];
	}
	
	public static function getExtension() {
		return 'yaml';
	}
	
	public function encode(L\CrudEvent $event) {
		$data = $event->getData();
		
		$data = Yaml::dump($data, $inline = 5, $indent = 2);
		
		$event->setData($data);
	}
	
	public function decode(L\CrudEvent $event) {
		$in = $event->getData();
		
		$data = Yaml::parse($in);
		
		$event->setData($data);
	}
}