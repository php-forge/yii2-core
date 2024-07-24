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

    public function testCacheSession()
    {
        $session = new CacheSession();

        $session->writeSession('test', 'sessionData');
        $this->assertEquals('sessionData', $session->readSession('test'));
        $session->destroySession('test');
        $this->assertEquals('', $session->readSession('test'));
    }

    public function testInvalidCache()
    {
        $this->expectException('\Exception');
        new CacheSession(['cache' => 'invalid']);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13537
     */
    public function testNotWrittenSessionDestroying()
    {
        $session = new CacheSession();

        $session->set('foo', 'bar');
        $this->assertEquals('bar', $session->get('foo'));

        $this->assertTrue($session->destroySession($session->getId()));
    }

    public function testInitUseStrictMode()
    {
        $this->initStrictModeTest(CacheSession::class);
    }

    public function testUseStrictMode()
    {
        $this->useStrictModeTest(CacheSession::class);
    }
}
