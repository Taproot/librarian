<?php

namespace Taproot\Librarian\Listener;

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
class LibYamlListener implements EventDispatcher\EventSubscriberInterface {
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
	
	public function __construct() {
		if (!extension_loaded('yaml'))
			throw new Exception('LibYamlListener: requires the yaml extension to function, maybe try SymfonyYamlListener?');
	}
	
	public function encode(L\CrudEvent $event) {
		$data = $event->getData();
		
		$data = yaml_emit($data, YAML_UTF8_ENCODING);
			
		$event->setData($data);
	}
	
	public function decode(L\CrudEvent $event) {
		$in = $event->getData();
		
		$data = yaml_parse($in);
		
		$event->setData($data);
	}
}