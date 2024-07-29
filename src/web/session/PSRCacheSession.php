<?php

declare(strict_types=1);

namespace yii\web\session;

use Psr\SimpleCache\CacheInterface;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\web\session\handler\PSRCacheSessionHandler;

/**
 * PSRCacheSession implements a session component using PSR-16 cache as storage medium.
 *
 * The cache being used can be any cache application component.
 * The ID of the cache application component is specified via PSR\SimpleCache\CacheInterface or its DI container
 * reference.
 *
 * Beware, by definition PSRCache storage are volatile, which means the data stored on them may be swapped out and get
 * lost. Therefore, you must make sure the PSRCache used by this component is NOT volatile. If you want to use database
 * as storage medium, [[DbSession]] is a better choice.
 *
 * The following example shows how you can configure the application to use PSRCacheSession:
 * Add the following to your application config under `components`:
 *
 * ```php
 * 'session' => [
 *     'class' => 'yii\web\session\PSRCacheSession',
 *     // 'cache' => new \Yiisoft\Cache\FileCache(Yii::getAlias('@runtime/cache')),
 * ]
 * ```
 */
class PSRCacheSession extends Session
{
    /**
     * @var CacheInterface|array|string|null the cache object or the application component ID of the cache object.
     * The session data will be stored using this cache object.
     *
     * After the CacheSession object is created, if you want to change this property,
     * you should only assign it with a cache object.
     */
    public CacheInterface|array|string|null $cache = CacheInterface::class;

    /**
     * Initializes the PSRCacheSession component.
     * This method will initialize the [[cache]] property to make sure it refers to a valid cache.
     *
     * @throws InvalidConfigException if [[cache]] is invalid.
     */
    public function init()
    {
        parent::init();

        $this->cache = Instance::ensure($this->cache, CacheInterface::class);
        $this->_handler ??= Instance::ensure(
            [
                'class' => PSRCacheSessionHandler::class,
                '__construct()' => [$this->cache],
            ]
        );
    }
}
