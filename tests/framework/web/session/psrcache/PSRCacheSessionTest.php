<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\cache;

use Psr\SimpleCache\CacheInterface;
use Yii;
use yii\web\session\handler\PSRCacheSessionHandler;
use yii\web\session\PSRCacheSession;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\File\FileCache;
use yiiunit\framework\web\session\FlashTestTrait;
use yiiunit\framework\web\session\SessionTestTrait;

/**
 * @group web
 * @group session-psr-cache
 */
class PSRCacheSessionTest extends \yiiunit\TestCase
{
    use FlashTestTrait;
    use SessionTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApplication();

        Yii::$app->set(CacheInterface::class, new FileCache(Yii::getAlias('@runtime/cache')));
    }

    protected function tearDown(): void
    {
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

    public function testDestroy(): void
    {
        $session = new PSRCacheSession();

        $session->set('test', 'sessionData');
        $this->assertEquals('sessionData', $session->get('test'));

        $this->assertTrue($session->destroy());
        $this->assertNull($session->get('test'));

        $session->setTimeout(0);

        $session->set('expired', 'expiredData');
        $this->assertSame('expiredData', $session->get('expired'));

        $this->assertTrue($session->destroy('expired'));
        $this->assertNull($session->get('expired'));

        $session->setTimeout(1440);
        $session->close();
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

        $session->close();
    }

    public function testSetAndGet(): void
    {
        $session = new PSRCacheSession();

        $session->set('test', 'sessionData');
        $this->assertEquals('sessionData', $session->get('test'));

        $session->destroy('test');
        $this->assertEquals('', $session->get('test'));
    }

    public function testInitUseStrictMode(): void
    {
        $this->initStrictModeTest(PSRCacheSession::class);
    }

    public function testUseStrictMode(): void
    {
        $this->useStrictModeTest(PSRCacheSession::class);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13537
     */
    public function testWrittenSessionDestroying(): void
    {
        $session = new PSRCacheSession();

        $session->set('foo', 'bar');
        $this->assertEquals('bar', $session->get('foo'));

        $this->assertTrue($session->destroy($session->getId()));
        $this->assertNull($session->get('foo'));
    }

    public function testAddFlash(): void
    {
        $this->add(PSRCacheSession::class);
    }

    public function testAddToExistingArrayFlash(): void
    {
        $this->addToExistingArray(PSRCacheSession::class);
    }

    public function testAddValueToExistingNonArrayFlash(): void
    {
        $this->addValueToExistingNonArray(PSRCacheSession::class);
    }

    public function testAddWithRemoveFlash(): void
    {
        $this->addWithRemove(PSRCacheSession::class);
    }

    public function testGetFlash(): void
    {
        $this->get(PSRCacheSession::class);
    }

    public function testGellAllFlash(): void
    {
        $this->getAll(PSRCacheSession::class);
    }

    public function testGetWithRemoveFlash(): void
    {
        $this->getWithRemove(PSRCacheSession::class);
    }

    public function testHasFlash(): void
    {
        $this->has(PSRCacheSession::class);
    }

    public function testRemoveFlash(): void
    {
        $this->remove(PSRCacheSession::class);
    }

    public function testRemoveAllFlash(): void
    {
        $this->removeAll(PSRCacheSession::class);
    }

    public function testSetFlash(): void
    {
        $this->set(PSRCacheSession::class);
    }
}
