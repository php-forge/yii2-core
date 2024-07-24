<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use Yii;
use yii\web\CacheSession;
use Yiisoft\Cache\File\FileCache;

/**
 * @group web
 */
class CacheSessionTest extends \yiiunit\TestCase
{
    use SessionTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();

        Yii::$app->set('cache', new FileCache(Yii::getAlias('@runtime/cache')));
    }

    public function testCacheSession(): void
    {
        $session = new CacheSession();

        $session->set('test', 'sessionData');
        $this->assertEquals('sessionData', $session->readSession('test'));

        $session->remove('test');
        $this->assertEquals('', $session->readSession('test'));
    }

    public function testInvalidCache(): void
    {
        $this->expectException('\Exception');

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
        $this->assertTrue($session->destroySession($session->getId()));
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
