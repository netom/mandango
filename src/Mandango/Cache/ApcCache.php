<?php

/*
 * This file is part of Mandango.
 *
 * (c) Fábián Tamás László <giganetom@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango\Cache;

/**
 * ApcuCache.
 *
 * @author Fábián Tamás László <giganetom@gmail.com>
 */
class ApcCache implements CacheInterface
{
    private static $PREFIX = 'MDG_';

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        $k = self::$PREFIX . $key;
        return apc_exists($k);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $k = self::$PREFIX . $key;
        return apc_exists($k) ? apc_fetch($k) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $k = self::$PREFIX . $key;
        apc_store($k, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $k = self::$PREFIX . $key;
        apc_delete($k);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        apc_clear_cache('user');
    }
}
