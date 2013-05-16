<?php

namespace Taproot\Librarian;

interface LibrarianInterface {
	const GET_EVENT = 'crud.get';
	const PUT_EVENT = 'crud.put';
	const DELETE_EVENT = 'crud.delete';
	const BUILD_ENVIRONMENT_EVENT = 'environment.build';
	const BUILD_INDEXES = 'indexes.build';
	const CLEAR_INDEXES = 'indexes.clear';
	
	/**
	 * Constructor
	 */
	public function __construct($namespace, array $config = [], array $indexes = []);
	
	/**
	 * Build Environment
	 * 
	 * Sets up the environment (DB, tables, folders) as necessary for all the indexes
	 * and CRUD to successfully work.
	 */
	public function buildEnvironment();
	
	/**
	 * Build Indexes
	 * 
	 * Iterates through all known documents and rebuilds any out of date indexes. Ideal
	 * for running on fresh data or after a server migration. Not something you want to
	 * run all the time, it can take a while.
	 * 
	 * This method resets+rebuilds indexes for each existing document but will not remove
	 * records for deleted documents. Use LibrarianInterface::clearIndexes for that.
	 */
	public function buildIndexes();
	
	/**
	 * Clear Indexes
	 * 
	 * Removes all index records from the database.
	 */
	public function clearIndexes();
	
	/**
	 * Get Item
	 * 
	 * Fetches a document.
	 * 
	 * @throws CrudException If the document is not found or is unreachable
	 * @return array The document
	 */
	public function get($id);
	
	/**
	 * Put Item
	 * 
	 * Saves/overwrites a document.
	 * 
	 * @param array $item the document to save
	 * @throws CrudException if the document is unreachable
	 */
	public function put(array $item);
	
	/**
	 * Delete Item
	 * 
	 * Deletes the document with $id
	 */
	public function delete($id);
	
	/**
	 * Query Items
	 * 
	 * Build a Query object which can then be filtered and fetched.
	 * 
	 * @param int $limit The maximum number of results to return
	 * @param array $orderBy An assoc. array of indexName => direction to order by
	 * @return Query
	 */
	public function query($limit = 20, array $orderBy = []);
}