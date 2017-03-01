<?php

/**
 * 2.0 concepts
 */

// Problems to overcome:
// * id / object cache does not impement cleaning or limiting, eats up memory
// * Mondator is cumbersome, yet not so efficient. Get rid of it.
// * 

// Documents as PHP objects:

class User implements ArrayAccess
{
    public function save()
    {
    }

    public function getName()
    {
    }

    public function offsetExists($k)
    {
    }
    
    public function offsetGet($k)
    {
    }

    public function offsetSet($k, $v)
    {
    }

    public function offsetUnset($k)
    {
    }

    public function toArray()
    {
    }
}

$user = new User();

$user->save(); // Persists the document to all bound collections.

$user->getName(); // Query field

$user['name']; // Same as above

$user->toArray(); // Returns an array

// A bound collection is a MongoDB collection that stores a document, or part(s) of it.
// Documents can be stored in multiple databases, and collections
// A document instance has zero or one 'home collection' where it is saved or read from
// purely embedded documents does not have home collections
// purely stand-alone and denormalized documents must have exactly one home collection
// Home collection and database can be changed at runtime in the repository
// Reference fields are store as MongoDB IDs or ID arrays
// One way and two-way references, two-way being declared in a single place
// Standalone document: one collection, stored as root documents
// Embedded document: Stored in other documents, has no home collection, no _id
// Denormalized document: has _id and home collection, but parts of it is stored in other documents
// Migrations (?)
// Field cache: use hash of stack trace to remember which fields to fetch
// Field arrays should be fetched as a whole, but embedded documents could be fetched partially
