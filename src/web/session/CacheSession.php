<?php

declare(strict_types=1);

namespace yii\web\session;

use Psr\SimpleCache\CacheInterface;
use yii\di\Instance;
use yii\web\session\handler\CacheSessionHandler;

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
 */
class CacheSession extends Session
{
    /**
     * @var CacheInterface|array|string the cache object or the application component ID of the cache object.
     * The session data will be stored using this cache object.
     *
     * After the CacheSession object is created, if you want to change this property,
     * you should only assign it with a cache object.
     */
    public CacheInterface|array|string $cache = CacheInterface::class;

    /**
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();

        $this->cache = Instance::ensure($this->cache, CacheInterface::class);

        /** @var CacheSessionHandler $_handler */
        $this->_handler = new CacheSessionHandler($this->cache);
    }
}
