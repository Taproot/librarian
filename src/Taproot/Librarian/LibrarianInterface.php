<?php

namespace Taproot\Librarian;

interface LibrarianInterface {
	const GET_EVENT = 'crud.get';
	const PUT_EVENT = 'crud.put';
	const DELETE_EVENT = 'crud.delete';
	const BUILD_ENVIRONMENT_EVENT = 'environment.build';
	const BUILD_INDEXES = 'indexes.build';
	
	/**
	 * Constructor
	 */
	public function __construct($namespace, array $config = [], array $indexes = []);
	
	/**
	 * Build Environment
	 */
	public function buildEnvironment();
	
	/**
	 * Build Indexes
	 */
	public function buildIndexes();
	
	/**
	 * Get Item
	 */
	public function get($id);
	
	/**
	 * Put Item
	 */
	public function put(array $item);
	
	/**
	 * Delete Item
	 */
	public function delete($id);
	
	/**
	 * Query Items
	 */
	public function query($limit = 20, $orderBy = []);
}