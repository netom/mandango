<?php

/*
 * This file is part of Mandango.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango\Tests\Type;

use Mandango\Type\Container;
use Mandango\Type\Type;

class TestingType extends Type
{
    public function toMongo($value)
    {
    }

    public function toPHP($value)
    {
    }

    public function toMongoInString()
    {
    }

    public function toPHPInString()
    {
    }
}

class ContainerTest extends TestCase
{
    public function testHas()
    {
        $this->assertTrue(Container::has('string'));
        $this->assertFalse(Container::has('no'));
    }

    public function testAdd()
    {
        Container::add('testing', 'Mandango\Tests\Type\TestingType');
        $this->assertTrue(Container::has('testing'));

        $this->assertInstanceOf('Mandango\Tests\Type\TestingType', Container::get('testing'));
    }

    public function testAddAlreadyExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        Container::add('string', 'Mandango\Tests\Type\TestingType');
    }

    public function testAddClassNotSubclassType()
    {
        $this->expectException(\InvalidArgumentException::class);
        Container::add('testing', '\DateTime');
    }

    public function testGet()
    {
        $string = Container::get('string');
        $float  = Container::get('float');

        $this->assertInstanceOf('Mandango\Type\StringType', $string);
        $this->assertInstanceOf('Mandango\Type\FloatType', $float);
    }

    public function testGetNotExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        Container::get('no');
    }

    public function testRemove()
    {
        Container::remove('string');
        $this->assertFalse(Container::has('string'));
    }

    public function testRemoveNotExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        Container::remove('no');
    }

    public function testResetTypes()
    {
        Container::add('testing', 'Mandango\Tests\Type\TestingType');
        Container::reset();

        $this->assertTrue(Container::has('string'));
        $this->assertFalse(Container::has('testing'));
    }
}
