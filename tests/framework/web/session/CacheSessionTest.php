<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use Psr\SimpleCache\CacheInterface;
use Yii;
use yii\web\session\CacheSession;
use Yiisoft\Cache\File\FileCache;

/**
 * @group web
 * @group session-cache
 */
class CacheSessionTest extends \yiiunit\TestCase
{
    use SessionTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
        Yii::$app->set(CacheInterface::class, new FileCache(Yii::getAlias('@runtime/cache')));
    }

    public function testCacheSession(): void
    {
        $session = new CacheSession();

        $session->set('test', 'sessionData');
        $this->assertEquals('sessionData', $session->get('test'));

        $session->destroy('test');
        $this->assertEquals('', $session->get('test'));
    }

    public function testInvalidCache(): void
    {
        $this->expectException('\Exception');
        $this->expectExceptionMessage('Failed to instantiate component or class "invalid".');

        new CacheSession(['cache' => 'invalid']);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13537
     */
    public function testNotWrittenSessionDestroying(): void
    {
        $session = new CacheSession();

        $session->set('foo', 'bar');
        $this->assertEquals('bar', $session->get('foo'));

        $this->assertTrue($session->destroy($session->getId()));
    }

    public function testInitUseStrictMode(): void
    {
        $this->initStrictModeTest(CacheSession::class);
    }

    public function testUseStrictMode(): void
    {
        $this->useStrictModeTest(CacheSession::class);
    }
}
