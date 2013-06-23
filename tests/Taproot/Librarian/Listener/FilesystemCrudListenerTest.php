<?php

namespace Taproot\Librarian\Test\Listener;

use Taproot\Librarian\Listener\FilesystemCrudListener;

class FilesystemCrudListenerTest extends \PHPUnit_Framework_TestCase {
	public function testClassExists() {
		$l = new FilesystemCrudListener([
			'path' => __DIR__ . '/../../../tmp_test_data/',
			'extension' => '.json',
			'idField' => 'id'
		]);
	}
	
	public function testEnsurePathExistsCreatesNestedDirectory() {
		$l = new FilesystemCrudListener([
			'path' => __DIR__ . '/../../../tmp_test_data/nonexistant-folder/another-nonexistant-folder',
			'extension' => '.json',
			'idField' => 'id'
		]);
		
		if (is_dir($l->getPath())) {
			rmdir($l->getPath());
			rmdir(dirname($l->getPath()));
		}
		
		$this->assertFileNotExists($l->getPath());
		
		$l->ensurePathExists();
		
		$this->assertFileExists($l->getPath());
		
		rmdir($l->getPath());
		rmdir(dirname($l->getPath()));
	}
	
	public function testGetAllDocumentPathsReturnsEmptyArrayIfNoDocumentsFound() {
		$l = new FilesystemCrudListener([
			'path' => __DIR__ . '/../../../tmp_test_data/nonexistant-folder/another-nonexistant-folder',
			'extension' => '.json',
			'idField' => 'id'
		]);
		
		$l->ensurePathExists();
		
		$this->assertEquals([], $l->getAllDocumentPaths());
		
		rmdir($l->getPath());
		rmdir(dirname($l->getPath()));
	}
}