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

/**
 * PolymorphicReferenceGroup.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
class PolymorphicReferenceGroup extends PolymorphicGroup
{
    private $parent;
    private $field;
    private $discriminatorMap;

    /**
     * Constructor.
     *
     * @param string                              $discriminatorField The discriminator field.
     * @param \Mandango\Document\AbstractDocument $parent             The parent document.
     * @param string                              $field              The reference field.
     * @param array|Boolean                       $discriminatorMap   The discriminator map if exists, otherwise false.
     *
     * @api
     */
    public function __construct($discriminatorField, $parent, $field, $discriminatorMap = false)
    {
        parent::__construct($discriminatorField);

        $this->parent = $parent;
        $this->field  = $field;
        $this->discriminatorMap = $discriminatorMap;
    }

    /**
     * Returns the parent document.
     *
     * @return \Mandango\Document\AbstractDocument The parent document.
     *
     * @api
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns the reference field.
     *
     * @return string The reference field.
     *
     * @api
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Returns the discriminator map.
     *
     * @return array|Boolean The discriminator map.
     *
     * @api
     */
    public function getDiscriminatorMap()
    {
        return $this->discriminatorMap;
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitializeSavedData()
    {
        return (array) $this->getParent()->get($this->getField());
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitializeSaved($data)
    {
        $data = parent::doInitializeSaved($data);

        $parent = $this->getParent();
        $mandango = $parent->getMandango();

        $discriminatorField = $this->getDiscriminatorField();
        $discriminatorMap = $this->getDiscriminatorMap();

        $ids = array();
        foreach ($data as $datum) {
            if ($discriminatorMap) {
                $documentClass = $discriminatorMap[$datum[$discriminatorField]];
            } else {
                $documentClass = $datum[$discriminatorField];
            }
            $ids[$documentClass][] = $datum['id'];
        }

        $documents = array();
        foreach ($ids as $documentClass => $documentClassIds) {
            foreach ((array) $mandango->getRepository($documentClass)->findById($documentClassIds) as $document) {
                $documents[] = $document;
            }
        }

        return $documents;
    }
}
