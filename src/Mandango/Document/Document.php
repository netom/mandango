<?php

/*
 * This file is part of Mandango.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango\Document;

/**
 * The base class for documents.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
abstract class Document extends AbstractDocument
{
    private $isNew = true;
    private $id;
    private $queryHashes = array();

    /**
     * Returns the repository.
     *
     * @return \Mandango\Repository The repository.
     *
     * @api
     */
    public function getRepository()
    {
        return $this->getMandango()->getRepository(get_class($this));
    }

    /**
     * Set the id of the document.
     *
     * @param mixed $id The id.
     *
     * @return \Mandango\Document\Document The document (fluent interface).
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Returns the id of document.
     *
     * @return \MongoDB\BSON\ObjectID|null The id of the document or null if it is new.
     *
     * @api
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * INTERNAL. Returns if the document is new.
     *
     * @param Boolean $isNew If the document is new.
     *
     * @return \Mandango\Document\Document The document (fluent interface).
     */
    public function setIsNew($isNew)
    {
        $this->isNew = (Boolean) $isNew;

        return $this;
    }

    /**
     * Returns if the document is new.
     *
     * @return bool Returns if the document is new.
     *
     * @api
     */
    public function isNew()
    {
        return $this->isNew;
    }

    /**
     * Create an options array to be used with find/insert/update/delete operations
     * 
     * It automatically creates a "session" key if there's a current session
     * with the connection.
     * 
     * @param array $options The options
     * @return array
     */
    public function createOptions(array $options = [])
    {
        $session = $this->getRepository()->getSession();
        if (null !== $session && !array_key_exists('session', $options)) {
            $options['session'] = $session;
        }
        return $options;
    }

    /**
     * Refresh the document data from the database.
     *
     * @return \Mandango\Document\Document The document (fluent interface).
     *
     * @throws \LogicException
     *
     * @api
     */
    public function refresh(array $options = [])
    {
        if ($this->isNew()) {
            throw new \LogicException('The document is new.');
        }

        $this->setDocumentData($this->getRepository()->getCollection()->findOne(
            [ '_id' => $this->getId() ],
            $this->createOptions($options)
        ), true);

        return $this;
    }

    /**
     * Save the document.
     *
     * @param array $options The options for the insertMany or update operation, it depends on if the document is new or not (optional).
     *
     * @return \Mandango\Document\Document The document (fluent interface).
     *
     * @api
     */
    public function save(array $options = [])
    {
        if ($this->isNew()) {
            $insertManyOptions = $options;
            $updateOptions = [];
        } else {
            $insertManyOptions = [];
            $updateOptions = $options;
        }

        $this->getRepository()->save($this, $insertManyOptions, $updateOptions);

        return $this;
    }

    /**
     * Delete the document.
     *
     * @param array $options The options for the remove operation (optional).
     *
     * @api
     */
    public function delete(array $options = [])
    {
        $this->getRepository()->delete($this, $options);
    }

    /**
     * Adds a query hash.
     *
     * @param string $hash The query hash.
     */
    public function addQueryHash($hash)
    {
        $this->queryHashes[$hash] = 1;
    }

    /**
     * Returns the query hashes.
     *
     * @return array The query hashes.
     */
    public function getQueryHashes()
    {
        return $this->queryHashes;
    }

    /**
     * Removes a query hash.
     *
     * @param string $hash The query hash.
     */
    public function removeQueryHash($hash)
    {
        unset($this->queryHashes[$hash]);
    }

    /**
     * Clear the query hashes.
     */
    public function clearQueryHashes()
    {
        $this->queryHashes = array();
    }

    /**
     * Add a field cache.
     */
    public function addFieldCache($field)
    {
        $cache = $this->getMandango()->getCache();

        foreach ($this->getQueryHashes() as $hash => $_) {
            $value = $cache->has($hash) ? $cache->get($hash) : array();
            if (!is_array($value)) {
                $value = array();
            }
            $value['fields'][$field] = 1;
            $cache->set($hash, $value);
        }
    }

    /**
     * Adds a reference cache
     */
    public function addReferenceCache($reference)
    {
        $cache = $this->getMandango()->getCache();

        foreach ($this->getQueryHashes() as $hash => $_) {
            $value = $cache->has($hash) ? $cache->get($hash) : array();
            if (!isset($value['references']) || !in_array($reference, $value['references'])) {
                $value['references'][] = $reference;
                $cache->set($hash, $value);
            }
        }
    }

    public function preInsertEvent() {}
    public function postInsertEvent() {}
    public function preUpdateEvent() {}
    public function postUpdateEvent() {}
    public function preDeleteEvent() {}
    public function postDeleteEvent() {}
}
