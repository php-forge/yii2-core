<?php

declare(strict_types=1);

namespace yii\web;

use Psr\SimpleCache\CacheInterface;
use yii\caching\CacheKeyNormalizer;
use yii\di\Instance;

/**
 * CacheSession implements a session component using cache as storage medium.
 *
 * The cache being used can be any cache application component.
 * The ID of the cache application component is specified via [[cache]], which defaults to 'cache'.
 *
 * Beware, by definition cache storage are volatile, which means the data stored on them may be swapped out and get
 * lost. Therefore, you must make sure the cache used by this component is NOT volatile. If you want to use database as
 * storage medium, [[DbSession]] is a better choice.
 *
 * The following example shows how you can configure the application to use CacheSession:
 * Add the following to your application config under `components`:
 *
 * ```php
 * 'session' => [
 *     'class' => 'yii\web\CacheSession',
 *     // 'cache' => 'mycache',
 * ]
 * ```
 *
 * @property-read bool $useCustomStorage Whether to use custom storage.
 */
class CacheSession extends Session
{
    /**
     * @var CacheInterface|array|string the cache object or the application component ID of the cache object.
     * The session data will be stored using this cache object.
     *
     * After the CacheSession object is created, if you want to change this property,
     * you should only assign it with a cache object.
     *
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public CacheInterface|array|string $cache = CacheInterface::class;

    /**
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();
        $this->cache = Instance::ensure($this->cache, CacheInterface::class);
        $this->handler = new CacheSessionHandler($this);
    }
}
