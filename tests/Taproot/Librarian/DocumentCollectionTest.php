<?php

namespace Taproot\Librarian\Test;

use \Exception;
use Taproot\Librarian\Librarian;
use Taproot\Librarian\DocumentCollection;

class DocumentCollectionTest extends \PHPUnit_Framework_TestCase {
	public $l;
	
	public function setUp() {
		$this->l = $this->getMockBuilder('Taproot\Librarian\Librarian')
			->disableOriginalConstructor()
			->getMock();
			
		$this->l->expects($this->any())
			->method('get')
			->will($this->returnArgument(0));
	}
	
	public function testCountsCorrectly() {
		$c = new DocumentCollection([1, 5, 6], $this->l);
		
		$this->assertEquals(3, count($c));
	}
	
	public function testExposesIds() {
		$ids = [1, 4, 6, 8];
		$c = new DocumentCollection($ids, $this->l);
		
		$this->assertEquals($ids, $c->getIds());
	}
	
	public function testCanReverseIds() {
		$ids = [1, 5, 7, 8];
		$c = new DocumentCollection($ids, $this->l);
		
		$reversedIds = array_reverse($ids);
		
		$c->reverse();
		
		$this->assertEquals($reversedIds, $c->getIds());	
	}
	
	public function testSettingOffsetsThrowsException() {
		try {
			$c = new DocumentCollection([], $this->l);
			$c[1] = 'NOTHING';
			$this->fail('No exception was thrown');
		} catch (Exception $e) {
			
		}
		
		try {
			$c = new DocumentCollection([], $this->l);
			unset($c[0]);
			$this->fail('No exception was thrown');
		} catch (Exception $e) {
			
		}
	}
	
	public function testIssetWorksCorrectly() {
		$c = new DocumentCollection([1], $this->l);
		
		$this->assertTrue(isset($c[0]));
		$this->assertFalse(isset($c[1]));
	}
	
	public function testIterationMethods() {
		$c = new DocumentCollection([1, 2, 3, 4, 5], $this->l);
		
		$this->assertEquals(0, $c->key());
		$this->assertTrue($c->valid());
		
		$c->next();
		$this->assertEquals(1, $c->key());
		
		$c->seek(3);
		$this->assertEquals(3, $c->key());
		
		$c->seek(16);
		$this->assertFalse($c->valid());
		
		$c->rewind();
		$this->assertEquals(0, $c->key());
	}
	
	public function testShouldReturnFirstItem() {
		$c = new DocumentCollection([1, 2, 3, 4], $this->l);
		
		$this->assertEquals(1, $c->first());
		$this->assertEquals(4, $c->last());
	}
}