<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\cache;

use Psr\SimpleCache\CacheInterface;
use Yii;
use yii\web\session\handler\PSRCacheSessionHandler;
use yii\web\session\PSRCacheSession;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\File\FileCache;
use yiiunit\framework\web\session\AbstractSession;

/**
 * @group web
 * @group session-cache
 */
class PSRCacheSessionTest extends AbstractSession
{
    protected function setUp(): void
    {
        $this->mockWebApplication();

        Yii::$app->set(CacheInterface::class, new FileCache(Yii::getAlias('@runtime/cache')));
        Yii::$app->set('session', ['class' => PSRCacheSession::class]);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        if (null !== $this->session) {
            $this->session->destroy();
        }

        $cache = Yii::$app->get(CacheInterface::class);
        $cache->clear();

        Yii::$app->set(CacheInterface::class, null);

        parent::tearDown();
    }

    public function testConfigWithArrayConfig(): void
    {
        $session = new PSRCacheSession(
            [
                'cache' => [
                    'class' => ArrayCache::class,
                ],
                '_handler' => [
                    'class' => PSRCacheSessionHandler::class,
                    '__construct()' => ['cache'],
                ],
            ],
        );

        $psrCache = $this->getInaccessibleProperty($session, 'cache');
        $this->assertInstanceOf(ArrayCache::class, $psrCache);
    }

    public function testConfigWithInvalidCache(): void
    {
        $this->expectException('\Exception');
        $this->expectExceptionMessage('Failed to instantiate component or class "invalid".');

        new PSRCacheSession(['cache' => 'invalid']);
    }

    public function testGarbageCollection(): void
    {
        $psrCache = new FileCache(Yii::getAlias('@runtime/cache'));
        $session = new PSRCacheSession(['cache' => $psrCache]);

        $session->setGCProbability(100);
        $session->setTimeout(0);
        $session->set('expired', 'expiredData');

        $this->assertSame('expiredData', $session->get('expired'));

        $session->close();

        $this->assertNull($session->get('expired'));

        $session->setGCProbability(0);
        $session->setTimeout(1440);

        $session->destroy();
    }
}
