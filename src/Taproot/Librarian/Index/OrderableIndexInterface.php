<?php

namespace Taproot\Librarian\Index;

/**
 * Orderable Index Interface
 *
 * 
 */
interface OrderableIndexInterface {
	/**
	 * Order By
	 * 
	 * `$direction` can be any string. `asc`, `desc`, `ascending` and `descending`
	 * SHOULD be accepted, other values can be used (e.g. `newestFirst`)
	 *
	 * @param string $direction which direction to order
	 */
	public function orderBy($direction);
}
