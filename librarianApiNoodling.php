<?php

// # Librarian API Codestorming
// 
// Much of these will probably become acceptance tests for Librarian.
// For the moment it’s a place to write a lot of how Librarian might work, preferably
// using real-world, existing problems, and to play with the API, seeing where we
// can simplify, where we can automate, which things can be convention over configuration

use Taproot\Librarian\Librarian;
use Taproot\Librarian\Index

// ## Setup

$l = new Librarian('notes', [
	'idField' => 'id',
	'path' => '/blah/blah/notes',
	'db' => 'info'
]);

$l->addIndexes([
	'tagged' => new Index\TaggedIndex(),
	'published' => new Index\DateTimeIndex('published'),
	'mentioning' => new Index\MentioningIndex('content'),
	'located' => new Index\LocatedIndex('tags', ['machineTagNamespace' => 'geo'])
]);

// Builds and executes any SQL required for the environment to be as defined so far
// Creates the data folder if it doesn’t exist
// Other, e.g. git repo init if not already
$l->buildEnvironment();

// Iterates through notes/indexes, building all the indexes for the content
$l->buildIndexes();

// Gets the item array for an id, throws an exception if it doesn’t exist
$l->get('id');

// Saves an item (id is determined by content)
$l->put([
	'id' => '125',
	'name' => 'Some Arbitrary Field'
]);

// Deletes an item
$l->delete('id');

// ## Indexed, Paged, Multiple Queries

// Get last 20 notes, order by published
// Returns an iterator which lazily fetches the data for the ids in the array returned 
// from the DB.
// Probably subclass ArrayIterator and override current and arrayCopy to fetch data
$notes = $l->query(20, $orderBy=['published' => 'newestFirst'])->fetch();

// Get last 20 notes tagged with 'food'
$foodieNotes = $l->query(20, $orderBy=['published' => 'newestFirst'])
	->tagged->with('food')
	->fetch();

// Get last 20 notes before 2013-01-07 12:00:00
// Would also accept a DateTime or Carbon instance
$eighteenthBirthdayAnticipationNotes = $l->query(20, $orderBy['published' => 'newestFirst'])
	->published->before('2013-01-07 12:00:00')
	->fetch();

// Datetime before/after permalink pagination
// Rev. chrono, ?before=datetime
$pageTwoFromPageOneNotes = $l->query(20, $orderBy['published' => 'newestFirst'])
	->published->before('Date of last note on page 1')
	->fetch();

// Chrono order, ?after=datetime
$pageThreeFromPageFourNotes = $l->query(20, $orderBy['published' => 'newestFirst'])
	->published->after('Date of first note on page four')
	->fetch();

// Get closest 20 notes to a particular location, within a particular radius
// TODO: duplication of lat/long data — how to deal with this?
//  - Infer one from the other? Which takes precedence?
$cafeStofanNotes = $l->query(20, $orderBy[
	'location' => ['closestTo' => ['lat', 'long']],
	'published' => 'newestFirst')
	->located->withinRadiusOfPoint(1000, 'lat', 'long')
	->fetch();

// Then, on the object returned by ->fetch():

count($cafeStofanNotes); // => number of results in this query
$cafeStofanNotes->totalCount() // (maybe, lazy) => total number of results matching non-pagination query

foreach ($cafeStofanNotes as $note) {
	// Do something with each note
	// Perhaps calculate the distance from lat, long as it isn’t returned in the query
}
