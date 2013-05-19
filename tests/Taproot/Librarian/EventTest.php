<?php

namespace Taproot\Librarian\Test;

use Taproot\Librarian;

class EventTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Librarian\Event
	 */
	protected $object;
	protected $l;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->l = new Librarian\Librarian('test');
		$this->object = new Librarian\Event($this->l);
	}

	/**
	 * @covers Taproot\Librarian\Event::getLibrarian
	 * @todo   Implement testGetLibrarian().
	 */
	public function testGetLibrarian()
	{
		$this->assertSame($this->l, $this->object->getLibrarian());
	}
}
