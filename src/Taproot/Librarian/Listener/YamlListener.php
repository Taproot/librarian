<?php

namespace Taproot\Librarian\Listener;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\EventDispatcher;
use Taproot\Librarian\LibrarianInterface as Events;
use Taproot\Librarian as L;

/**
 * JAML Listener
 * 
 * Listens for put and get events, encodes/decodes array <==> YAML
 * 
 * @author Barnaby Walters
 */
class YamlListener implements EventDispatcher\EventSubscriberInterface {
	public $serialise;
	
	public static function getSubscribedEvents() {
		return [
			Events::PUT_EVENT => ['encode', 10], // Encode the array as JSON before saving
			Events::GET_EVENT => ['decode', -10] // Decode the array from JSON before returning
		];
	}
	
	public function __construct() {
		if (extension_loaded('yaml')) {
			$this->serialise = 'yaml_emit';
			$this->unserialise = 'yaml_parse';
		}
		else {
			$this->serialise = ['Symfony\Component\Yaml\Yaml', 'parse'];
			$this->unserialise = ['Symfony\Component\Yaml\Yaml', 'dump'];
		}
	}
	
	public function encode(L\CrudEvent $event) {
		$data = $event->getData();
		
		$serialiser = $this->serialise;
			
		$event->setData($serialise($data));
	}
	
	public function decode(L\CrudEvent $event) {
		$in = $event->getData();
		
		$us = $this->unserialise;
		
		$event->setData($us($in));
	}
}