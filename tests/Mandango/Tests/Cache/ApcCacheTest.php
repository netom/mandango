<?php

/*
 * This file is part of Mandango.
 *
 * (c) Fábián Tamás László <giganetom@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango\Tests\Cache;

use Mandango\Cache\ApcuCache;

class ApcCacheTest extends CacheTestCase
{
    protected function getCacheDriver()
    {
        if (extension_loaded('apc')) {
            return new ApcCache();
        } elseif (extension_loaded('apcu')) {
            return new ApcuCache();
        }
        $this->fail("Neither APC nor APCu is available.");
    }
}
