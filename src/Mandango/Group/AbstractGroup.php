<?php

/*
 * This file is part of Mandango.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango\Group;

use Mandango\Document\Document;

/**
 * AbstractGroup.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
abstract class AbstractGroup implements \Countable, \IteratorAggregate
{
    /**
     * Saved documents of the group
     */
    private $saved;

    /**
     * Documents to be added to the group
     */
    private $add = array();

    /**
     * Documents to be removed from the group
     */
    private $remove = array();

    const MONGO_TYPE_ARRAY  = 0;
    const MONGO_TYPE_OBJECT = 1;

    protected $mongoType = self::MONGO_TYPE_ARRAY;

    /**
     * Adds document/s to the add queue of the group.
     *
     * @param \Mandango\Document\AbstractDocument|array $documents One or more documents.
     * @param boolean Wether to use the keys in the given array or not. Default is not to use them.
     *
     * @api
     */
    public function add($documents, $preserveKeys = false)
    {
        if (!is_array($documents)) {
            $documents = array($documents);
            $preserveKeys = false;
        }

        if ($preserveKeys) {
            foreach ($documents as $key => $document) {
                $this->add[$key] = $document;
            }
        } else {
            foreach ($documents as $document) {
                $this->add[] = $document;
            }
        }
    }

    /**
     * Returns the add queue of the group.
     *
     * @api
     */
    public function getAdd()
    {
        return $this->add;
    }

    /**
     * Clears the add queue of the group.
     *
     * @api
     */
    public function clearAdd()
    {
        $this->add = array();
    }

    /**
     * Adds document/s to the remove queue of the group.
     *
     * @param \Mandango\Document\AbstractDocument|array $documents One of more documents.
     *
     * @api
     */
    public function remove($documents)
    {
        if (!is_array($documents)) {
            $documents = array($documents);
        }

        foreach ($documents as $document) {
            $this->remove[] = $document;
        }
    }

    /**
     * Returns the remove queue of the group.
     *
     * @api
     */
    public function getRemove()
    {
        return $this->remove;
    }

    /**
     * Clears the remove queue of the group.
     *
     * @api
     */
    public function clearRemove()
    {
        $this->remove = array();
    }

    /**
     * Returns the saved documents of the group.
     */
    public function getSaved()
    {
        if (null === $this->saved) {
            $this->initializeSaved();
        }

        return $this->saved;
    }

    /**
     * Returns the saved + add - removed elements.
     *
     * @api
     */
    public function all()
    {
        $documents = array_merge($this->getSaved(), $this->getAdd());

        foreach ($this->getRemove() as $document) {
            if (false !== $key = array_search($document, $documents)) {
                unset($documents[$key]);
            }
        }

        return array_values($documents);
    }

    /**
     * Returns an array of arrays representing the contained documents
     */
    public function toArray(array $fields = array())
    {
        $arrays = array();
        foreach ($this->all() as $key => $document) {
            $arrays[$key] = $document->toArray($fields);
        }
        return $arrays;
    }

    /**
     * Implements the \IteratorAggregate interface.
     *
     * @api
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * Refresh the saved documents.
     *
     * @api
     */
    public function refreshSaved()
    {
        $this->initializeSaved();
    }

    /**
     * Initializes the saved documents.
     */
    private function initializeSaved()
    {
        $d = $this->doInitializeSavedData();
        $this->saved = $this->doInitializeSaved($d);
    }

    /**
     * Clears the saved documents.
     *
     * @api
     */
    public function clearSaved()
    {
        $this->_saved_data = null;
        $this->saved = null;
    }

    /**
     * Returns if the saved documents are initialized.
     *
     * @return bool If the saved documents are initialized.
     *
     * @api
     */
    public function isSavedInitialized()
    {
        return null !== $this->saved;
    }

    /**
     * Do the initialization of the saved documents data.
     *
     * @api
     */
    abstract protected function doInitializeSavedData();

    /**
     * Do the initialization of the saved documents.
     *
     * @api
     */
    abstract protected function doInitializeSaved(array $data);

    /**
     * Returns the number of all documents.
     *
     * @api
     */
    public function count()
    {
        return count($this->all());
    }

    /**
     * Replace all documents.
     *
     * @param array $documents An array of documents.
     *
     * @api
     */
    public function replace(array $documents)
    {
        $this->clearAdd();
        $this->clearRemove();

        $this->remove($this->getSaved());
        $this->add($documents);
    }

    /**
     * Resets the group (clear adds and removed, and saved if there are adds or removed).
     *
     * @api
     */
    public function reset()
    {
        if ($this->getAdd() || $this->getRemove()) {
            $this->clearSaved();
        }
        $this->clearAdd();
        $this->clearRemove();
    }
}
