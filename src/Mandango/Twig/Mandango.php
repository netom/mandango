<?php

/*
 * This file is part of Mandango.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango\Twig;

use Mandango\Id\IdGeneratorContainer;
use Mandango\Type\Container as TypeContainer;

/**
 * The "mandango" extension for twig (used in the Core Mondator extension).
 *
 * @author Pablo Díez <pablodip@gmail.com>
 */
class Mandango extends \Twig\Extension\AbstractExtension
{
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('ucfirst',    'ucfirst'),
            new \Twig\TwigFilter('var_export', function ($var) { return var_export($var, true);}),
            new \Twig\TwigFilter('addslashes', 'addslashes'),
        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('mandango_id_generator',          [$this, 'mandangoIdGenerator']),
            new \Twig\TwigFunction('mandango_id_generator_to_mongo', [$this, 'mandangoIdGeneratorToMongo']),
            new \Twig\TwigFunction('mandango_type_to_mongo',         [$this, 'mandangoTypeToMongo']),
            new \Twig\TwigFunction('mandango_type_to_php',           [$this, 'mandangoTypeToPHP']),
        ];
    }

    public function mandangoIdGenerator($configClass, $id, $indent = 8)
    {
        $idGenerator = IdGeneratorContainer::get($configClass['idGenerator']['name']);
        $code = $idGenerator->getCode($configClass['idGenerator']['options']);
        $code = str_replace('%id%', $id, $code);
        $code = static::indentCode($code, $indent);

        return $code;
    }

    public function mandangoIdGeneratorToMongo($configClass, $id, $indent = 8)
    {
        $idGenerator = IdGeneratorContainer::get($configClass['idGenerator']['name']);
        $code = $idGenerator->getToMongoCode();
        $code = str_replace('%id%', $id, $code);
        $code = static::indentCode($code, $indent);

        return $code;
    }

    public function mandangoTypeToMongo($type, $from, $to)
    {
        return strtr(TypeContainer::get($type)->toMongoInString(), array(
            '%from%' => $from,
            '%to%'   => $to,
        ));
    }

    public function mandangoTypeToPHP($type, $from, $to)
    {
        return strtr(TypeContainer::get($type)->toPHPInString(), array(
            '%from%' => $from,
            '%to%'   => $to,
        ));
    }

    public function getName()
    {
        return 'mandango';
    }

    static private function indentCode($code, $indent)
    {
        return str_replace("\n", "\n".str_repeat(' ', $indent), $code);
    }
}
