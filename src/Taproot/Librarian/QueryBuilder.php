<?php

namespace Taproot\Librarian;

use Doctrine\DBAL;

class QueryBuilder extends DBAL\Query\QueryBuilder {
	
	/**
	 * Clone
	 * 
	 * Adds deep-cloning to QueryBuilder so that changes made to one instance will not
	 * affect cloned instances. Extremely useful for creating a base query then multiple
	 * other similar queries.
	 * 
	 * Modelled on the suggestion in Jira and the Doctrine ORM equivalent.
	 * 
	 * @see http://www.doctrine-project.org/jira/browse/DDC-2313?page=com.atlassian.jirafisheyeplugin:fisheye-issuepanel
	 * @see https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/QueryBuilder.php
	 */
	public function __clone() {
		foreach ($this->sqlParts as $part => $elements) {
			if (is_array($elements)) {
				foreach($this->sqlParts[$part] as $i => $element) {
					if (is_object($element))
						$this->sqlParts[$part][$i] = clone $element;
				}
			} elseif (is_object($elements)) {
				$this->sqlParts[$part] = clone $elements;
			}
		}
		
		$params = [];
		foreach ($this->params as $param) {
			$params[] = clone $param;
		}
		
		$this->params = $params;
	}
}