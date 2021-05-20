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
 * EmbeddedGroup.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
class EmbeddedGroup extends Group
{
    /**
     * The root document of the group
     * 
     * Do not modify directly, use setRootAndPath()!
     */
    public $_root;

    /**
     * The path of the group relative to the root document
     * 
     * Do not modify directly, use setRootAndPath()!
     */
    public $_path;

    /**
     * 
     */
    protected $_saved_data;

    /**
     * Set the root and path of the embedded group.
     *
     * @param \Mandango\Document\Document $root The root document.
     * @param string                      $path The path.
     *
     * @api
     */
    public function setRootAndPath(Document $root, $path)
    {
        $this->_root = $root;
        $this->_path = $path;

        foreach ($this->getAdd() as $key => $document) {
            $document->setRootAndPath($this->_root, $this->_path . '._add' . $key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add($documents, $preserveKeys = false)
    {
        parent::add($documents, $preserveKeys);

        if ($this->_root) {
            foreach ($this->getAdd() as $key => $document) {
                $document->setRootAndPath($this->_root, $this->_path . '._add' . $key);
            }
        }
    }

    /**
     * Set the saved data.
     *
     * @param array $data The saved data.
     */
    public function setSavedData(array $data)
    {
        $this->_saved_data = $data;
    }

    /**
     * Returns the saved data.
     *
     * @return array|null The saved data or null if it does not exist.
     */
    public function getSavedData()
    {
        return  $this->_saved_data;
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitializeSavedData()
    {
        if ($this->_saved_data !== null) {
            return $this->_saved_data;
        }

        if (empty($this->_root) || $this->_root->isNew()) {
            return array();
        }

        /* TODO
         * 
         * Suboptimal workaround to field cache bug with EmbeddedGroup:
         * only add path until the first numeric element, but not that element.
         * 
         * Example:
         * 
         * If path is "comments.1.infos", only "comments" is added to the cache.
         * 
         * A better solution is to mark EmbeddedGroups, so the Query class can use
         * MongoDB's array projection operator to project document fields inside
         * arrays.
         */
        $path = [];
        foreach (explode('.', $this->_path) as $e) {
            if (is_numeric($e)) {
                break;
            }
            $path[] = $e;
        }
        $path = implode('.', $path);

        $this->_root->addFieldCache($path);

        $repository = $this->_root->getRepository();

        $result = $repository
            ->getCollection()
            ->findOne(
                [ '_id' => $this->_root->getId() ],
                [
                    $this->_path => true,
                    'session' => $repository->getSession()
                ]
            );

        return ($result && isset($result[$this->_path])) ? (array)$result[$this->_path] : [];
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitializeSaved(array $data)
    {
        if (null === $this->_root) {
            return [];
        }

        $documentClass = $this->getDocumentClass();
        $mandango = $this->_root->getMandango();

        $saved = [];
        foreach ($data as $key => $datum) {
            $saved[$key] = $document = new $documentClass($mandango);
            $document->setDocumentData($datum);
            $document->setRootAndPath($this->_root, $this->_path . '.' . $key);
        }

        return $saved;
    }
}
