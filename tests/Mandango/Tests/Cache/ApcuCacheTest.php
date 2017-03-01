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

class ApcuCacheTest extends CacheTestCase
{
    protected function getCacheDriver()
    {
        if (extension_loaded('apcu')) {
            if (php_sapi_name() === 'cli' && !ini_get('apc.enable_cli')) {
                $this->markTestSkipped(
                    'The APCu in CLI mode not available. Set "apc.enable_cli = 1" in php.ini.'
                );
            }

            return new ApcuCache();
        } else {
            $this->markTestSkipped();
        }
    }
}
