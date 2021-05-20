<?php

/*
 * This file is part of Mandango.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango;

/**
 * The base class for repositories.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
abstract class Repository
{
    /*
     * Setted by the generator.
     */
    protected $documentClass;
    protected $connectionName;
    protected $collectionName;

    private $mandango;
    private $identityMap;
    private $connection;
    private $collection;

    /**
     * Constructor.
     *
     * @param \Mandango\Mandango $mandango The mandango.
     *
     * @api
     */
    public function __construct(Mandango $mandango)
    {
        $this->mandango = $mandango;
        $this->identityMap = new IdentityMap();
    }

    /**
     * Returns the Mandango.
     *
     * @return \Mandango\Mandango The Mandango.
     *
     * @api
     */
    public function getMandango()
    {
        return $this->mandango;
    }

    /**
     * Returns the identity map.
     *
     * @return \Mandango\IdentityMapInterface The identity map.
     *
     * @api
     */
    public function getIdentityMap()
    {
        return $this->identityMap;
    }

    /**
     * Returns the document class.
     *
     * @return string The document class.
     *
     * @api
     */
    public function getDocumentClass()
    {
        return $this->documentClass;
    }

    /**
     * Returns the metadata.
     *
     * @return array The metadata.
     *
     * @api
     */
    public function getMetadata()
    {
        return $this->mandango->getMetadataFactory()->getClass($this->documentClass);
    }

    /**
     * Returns the connection name, or null if it is the default.
     *
     * @return string|null The connection name.
     *
     * @api
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Returns the collection name.
     *
     * @return string The collection name.
     *
     * @api
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * Returns the connection.
     *
     * @return \Mandango\Connection The connection.
     *
     * @api
     */
    public function getConnection()
    {
        if (!$this->connection) {
            if ($this->connectionName) {
                $this->connection = $this->mandango->getConnection($this->connectionName);
            } else {
                $this->connection = $this->mandango->getDefaultConnection();
            }
        }

        return $this->connection;
    }

    /**
     * Returns the collection.
     *
     * @return \MongoDB\Collection The collection.
     *
     * @api
     */
    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->getConnection()->getDatabase()->selectCollection($this->collectionName);
        }

        return $this->collection;
    }

    /**
     * Create a query for the repository document class.
     *
     * @param array $criteria The criteria for the query (optional).
     *
     * @return Query The query.
     *
     * @api
     */
    public function createQuery(array $criteria = array())
    {
        $class = $this->documentClass.'Query';
        $query = new $class($this);
        $query->criteria($criteria);

        return $query;
    }

    /**
     * Converts an id to use in Mongo.
     *
     * @param mixed $id An id.
     *
     * @return mixed The id to use in Mongo.
     */
    abstract public function idToMongo($id);

    /**
     * Converts an array of ids to use in Mongo.
     *
     * @param array $ids An array of ids.
     *
     * @return array The array of ids converted.
     */
    public function idsToMongo(array $ids)
    {
        foreach ($ids as &$id) {
            $id = $this->idToMongo($id);
        }

        return $ids;
    }

    /**
     * Find documents by id.
     *
     * @param array $ids An array of ids.
     *
     * @return array An array of documents.
     *
     * @api
     */
    public function findById(array $ids)
    {
        $mongoIds = $this->idsToMongo($ids);
        $cachedDocuments = $this->findCachedDocuments($mongoIds);

        if ($this->areAllDocumentsCached($cachedDocuments, $mongoIds)) {
            return $cachedDocuments;
        }

        $idsToQuery = $this->getIdsToQuery($cachedDocuments, $mongoIds);
        $queriedDocuments = $this->queryDocumentsByIds($idsToQuery);

        return array_merge($cachedDocuments, $queriedDocuments);
    }

    private function findCachedDocuments($mongoIds)
    {
        $documents = array();
        foreach ($mongoIds as $id) {
            if ($this->identityMap->has($id)) {
                $documents[$this->identityMap->idToString($id)] = $this->identityMap->get($id);
            }
        }

        return $documents;
    }

    private function areAllDocumentsCached($cachedDocuments, $mongoIds)
    {
        return count($cachedDocuments) == count($mongoIds);
    }

    private function getIdsToQuery($cachedDocuments, $mongoIds)
    {
        $ids = array();
        foreach ($mongoIds as $id) {
            if (!isset($cachedDocuments[$this->identityMap->idToString($id)])) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    private function queryDocumentsByIds($ids)
    {
        $criteria = array('_id' => array('$in' => $ids));

        return $this->createQuery($criteria)->all();
    }

    /**
     * Returns one document by id.
     *
     * @param mixed $id An id.
     *
     * @return \Mandango\Document\Document|null The document or null if it does not exist.
     *
     * @api
     */
    public function findOneById($id)
    {
        $id = $this->idToMongo($id);

        if ($this->identityMap->has($id)) {
            return $this->identityMap->get($id);
        }

        return $this->createQuery(array('_id' => $id))->one();
    }

    public function getSession()
    {
        $c = $this->getConnection();
        if (empty($c)) {
            throw new \UnexpectedValueException("Couldn't get the connection.");
        }
        return $c->getSession();
    }

    /**
     * Count documents.
     *
     * @param array $query The query (opcional, by default an empty array).
     *
     * @return integer The number of documents.
     *
     * @api
     */
    public function count(array $query = array())
    {
        return $this->getCollection()->count($query, [
            'session' => $this->getSession()
        ]);
    }

    /**
     * Updates documents.
     *
     * @param array $query     The query.
     * @param array $newObject The new object.
     * @param array $options   The options for the update operation (optional).
     *
     * @return mixed The result of the update collection method.
     */
    public function update(array $query, array $newObject, array $options = array())
    {
        if (!array_key_exists('session', $options)) {
            $options['session'] = $this->getSession();
        }
        return $this->getCollection()->updateMany($query, $newObject, $options);
    }

    /**
     * Remove documents.
     *
     * @param array $query   The query (optional, by default an empty array).
     * @param array $options The options for the remove operation (optional).
     *
     * @return mixed The result of the remove collection method.
     *
     * @api
     */
    public function remove(array $query = array(), array $options = array())
    {
        if (!array_key_exists('session', $options)) {
            $options['session'] = $this->getSession();
        }
        return $this->getCollection()->deleteMany($query, $options);
    }

    /**
     * Executes an aggregation framework pipeline on the collection.
     *
     * @param mixed $pipeline List of pipeline operations
     * @param array $options  Command options
     *
     * @return array The result
     *
     * @see \MongoDB\Collection::aggregate()
     *
     * @api
     */
    public function aggregate(array $pipeline, array $options = array())
    {
        if (!array_key_exists('session', $options)) {
            $options['session'] = $this->getSession();
        }
        return $this->getCollection()->aggregate($pipeline, $options);
    }

    /**
     * Shortcut to make a distinct command.
     *
     * @param string $field The field.
     * @param array  $query The query (optional).
     *
     * @return array The results.
     *
     * @api
     */
    public function distinct($field, array $query = array())
    {
        return $this->getCollection()->distinct($field, $query, [
            'session' => $this->getSession()
        ]);
    }

    /**
     * Shortcut to make map reduce.
     *
     * @param mixed $map     The map function.
     * @param mixed $reduce  The reduce function.
     * @param array $out     The out.
     * @param array $query   The query (optional).
     * @param array $options Extra options for the command (optional).
     *
     * @return array With the
     *
     * @throws \RuntimeException If the database returns an error.
     */
    public function mapReduce($map, $reduce, array $out, array $query = array(), array $command = array(), $options = array())
    {
        $command = array_merge($command, array(
            'mapreduce' => $this->getCollectionName(),
            'map'       => is_string($map) ? new \MongoDB\BSON\Javascript($map) : $map,
            'reduce'    => is_string($reduce) ? new \MongoDB\BSON\Javascript($reduce) : $reduce,
            'out'       => $out,
            'query'     => $query,
        ));

        $result = $this->command($command, $options);

        if (!$result['ok']) {
            throw new \RuntimeException($result['errmsg']);
        }

        if (isset($out['inline']) && $out['inline']) {
            return $result['results'];
        }

        return $this->getDatabase()->selectCollection($result['result'])->find();
    }

    private function command($command, $options = array())
    {
        if (!array_key_exists('session', $options)) {
            $options['session'] = $this->getSession();
        }
        return $this->getDatabase()->command($command, $options);
    }

    private function getDatabase()
    {
        return $this->getConnection()->getDatabase();
    }

    /**
     * Remove all indexes
     */
    public function dropIndexes()
    {
        $c = $this->getCollection();
        foreach ($c->listIndexes() as $i) {
            $n = $i->getName();
            if ($n == '_id_') {
                break;
            }
            $c->dropIndex($n, [
                'session' => $this->getSession()
            ]);
        }
    }
}
