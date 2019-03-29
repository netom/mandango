<?php

/*
 * This file is part of Mandango.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango\Id;

use Mandango\Document\Document;

/**
 * Generates a sequence.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 */
class SequenceIdGenerator extends BaseIdGenerator
{
    /**
     * {@inheritdoc}
     */
    public function getCode(array $options)
    {
        $increment = isset($options['increment']) ? $options['increment'] : 1;
        $start = isset($options['start']) ? $options['start'] : null;

        // increment
        if (!is_int($increment) || 0 === $increment) {
            throw new \InvalidArgumentException('The option "increment" must be an integer distinct of 0.');
        }

        // start
        if (null === $start) {
            $start = $increment > 0 ? 1 : -1;
        } elseif (!is_int($start) || 0 === $start) {
            throw new \InvalidArgumentException('The option "start" must be an integer distinct of 0.');
        }

        return <<<EOF
%id% = \$commandResult = iterator_to_array(\$repository->getConnection()->getDatabase()->command([
    'findandmodify' => 'mandango_sequence_id_generator',
    'query'         => array('_id' => \$repository->getCollectionName()),
    'update'        => [
        '\$inc' => ['sequence' => $increment]
    ],
    'new'           => true,
    'upsert'        => true
], ['readPreference' => new \MongoDB\Driver\ReadPreference(\MongoDB\Driver\ReadPreference::RP_PRIMARY)]))[0]['value']['sequence'];
EOF;
    }

    /**
     * {@inheritdoc}
     */
    public function getToMongoCode()
    {
        return <<<EOF
%id% = (int) %id%;
EOF;
    }
}
