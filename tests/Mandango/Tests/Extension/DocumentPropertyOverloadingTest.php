<?php

/*
 * This file is part of Mandango.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango\Tests\Extension;

use Mandango\Tests\TestCase;

class DocumentPropertyOverloadingTest extends TestCase
{
    public function test__set()
    {
        $article = $this->mandango->create('Model\Article');
        $article->title = 'foo';
        $this->assertSame('foo', $article->getTitle());
    }

    public function test__setNameNotExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        $article = $this->mandango->create('Model\Article');
        $article->no = 'foo';
    }

    public function test__get()
    {
        $article = $this->mandango->create('Model\Article');
        $article->setTitle('foo');
        $this->assertSame('foo', $article->title);
    }

    public function test__getNameNotExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        $article = $this->mandango->create('Model\Article');
        $article->no;
    }
}
