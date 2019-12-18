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

class DocumentArrayAccessTest extends TestCase
{
    public function testOffsetExists()
    {
        $this->expectException(\LogicException::class);
        $article = $this->mandango->create('Model\Article');
        isset($article['title']);
    }

    public function testOffsetSet()
    {
        $article = $this->mandango->create('Model\Article');
        $article['title'] = 'foo';
        $this->assertSame('foo', $article->getTitle());
    }

    public function testOffsetSetNameNotExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        $article = $this->mandango->create('Model\Article');
        $article['no'] = 'foo';
    }

    public function testOffsetGet()
    {
        $article = $this->mandango->create('Model\Article');
        $article->setTitle('bar');
        $this->assertSame('bar', $article['title']);
    }

    public function testOffsetGetNameNotExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        $article = $this->mandango->create('Model\Article');
        $article['no'];
    }

    public function testOffsetUnset()
    {
        $this->expectException(\LogicException::class);
        $article = $this->mandango->create('Model\Article');
        unset($article['title']);
    }
}
