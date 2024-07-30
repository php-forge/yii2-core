<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use Xepozz\InternalMocker\InternalMocker;
use Xepozz\InternalMocker\Mocker;
use Yii;
use yii\base\InvalidArgumentException;
use yii\log\Logger;
use yii\web\session\Session;
use yiiunit\TestCase;

/**
 * @group web
 * @group session
 */
final class SessionTest extends TestCase
{
    use FlashTestTrait;
    use SessionTestTrait;

    public function testCount(): void
    {
        $session = new Session();
        $session->open();

        $this->assertEquals(0, $session->count);

        $session->set('name', 'value');
        $this->assertSame(1, $session->count());

        $session->destroy();
    }

    /**
     * Test to prove that after Session::destroy session id set to old value.
     */
    public function testDestroySessionId(): void
    {
        $session = new Session();
        $session->open();

        $oldSessionId = @session_id();

        $this->assertNotEmpty($oldSessionId);

        $session->destroy();

        $newSessionId = @session_id();
        $this->assertNotEmpty($newSessionId);
        $this->assertEquals($oldSessionId, $newSessionId);
    }

    public function testGetCount(): void
    {
        $session = new Session();
        $session->open();

        $this->assertEquals(0, $session->getCount());

        $session->set('name', 'value');
        $this->assertEquals(1, $session->getCount());

        $session->destroy();
    }

    public function testHas(): void
    {
        $session = new Session();
        $session->open();

        $this->assertFalse($session->has('name'));

        $session->set('name', 'value');
        $this->assertTrue($session->has('name'));

        $session->destroy();
    }

    public function testIdIsSet(): void
    {
        $this->mockWebApplication();

        $originalCookie = $_COOKIE;

        $_COOKIE['PHPSESSID'] = 'test';
        ini_set('session.use_cookies', '1');

        $session = new Session();

        $this->assertTrue($session->getHasSessionId());

        $session->destroy();

        $_COOKIE = $originalCookie;

        $this->destroyApplication();
    }

    public function testOffsetExists(): void
    {
        $session = new Session();
        $session->open();

        $this->assertFalse(isset($session['name']));

        $session['name'] = 'value';
        $this->assertTrue(isset($session['name']));

        $session->destroy();
    }

    public function testOffsetGet(): void
    {
        $session = new Session();
        $session->open();

        $this->assertNull($session['name']);

        $session['name'] = 'value';
        $this->assertEquals('value', $session['name']);

        $session->destroy();
    }

    public function testOffsetSet(): void
    {
        $session = new Session();
        $session->open();

        $session['name'] = 'value';
        $this->assertEquals('value', $session['name']);

        $session->destroy();
    }

    public function testOffsetUnset(): void
    {
        $session = new Session();
        $session->open();

        $session['name'] = 'value';
        $this->assertEquals('value', $session['name']);

        unset($session['name']);
        $this->assertNull($session['name']);

        $session->destroy();
    }

    public function testOpenFailure(): void
    {
        /** @var Session $session */
        $session = $this->getMockBuilder(Session::class)->onlyMethods(['getIsActive'])->getMock();
        $session->method('getIsActive')->willReturn(false);

        $logger = new Logger();
        $logger->flush();

        Yii::setLogger($logger);

        $session->open();

        $logs = $logger->messages;

        $this->assertCount(1, $logs);
        $this->assertSame(Logger::LEVEL_ERROR, $logs[0][1]);
        $this->assertSame('yii\web\session\Session::open', $logs[0][2]);

        Yii::setLogger(null);
    }

    /**
     * Test to prove that after Session::open changing session parameters will not throw exceptions and its values will
     * be changed as expected.
     */
    public function testParamsAfterSessionStart(): void
    {
        $session = new Session();
        $session->open();

        $oldUseTransparentSession = $session->getUseTransparentSessionID();
        $session->setUseTransparentSessionID(true);
        $newUseTransparentSession = $session->getUseTransparentSessionID();
        $this->assertNotEquals($oldUseTransparentSession, $newUseTransparentSession);
        $this->assertTrue($newUseTransparentSession);

        //without this line phpunit will complain about risky tests due to unclosed buffer
        $session->setUseTransparentSessionID(false);
        $oldTimeout = $session->getTimeout();
        $session->setTimeout(600);
        $newTimeout = $session->getTimeout();
        $this->assertNotEquals($oldTimeout, $newTimeout);
        $this->assertEquals(600, $newTimeout);

        $oldUseCookies = $session->getUseCookies();
        $session->setUseCookies(false);
        $newUseCookies = $session->getUseCookies();

        if (null !== $newUseCookies) {
            $this->assertNotEquals($oldUseCookies, $newUseCookies);
            $this->assertFalse($newUseCookies);
        }

        $oldGcProbability = $session->getGCProbability();
        $session->setGCProbability(100);
        $newGcProbability = $session->getGCProbability();
        $this->assertNotEquals($oldGcProbability, $newGcProbability);
        $this->assertEquals(100, $newGcProbability);
        $session->setGCProbability($oldGcProbability);
    }

    public function testRegenerateID(): void
    {
        $session = new Session();
        $session->open();

        $oldSessionId = $session->getId();

        $session->regenerateID();

        $newSessionId = $session->getId();

        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    public function testRemove(): void
    {
        $session = new Session();
        $session->open();

        $session->set('name', 'value');
        $this->assertEquals('value', $session->get('name'));

        $session->remove('name');
        $this->assertNull($session->get('name'));

        $session->destroy();
    }

    public function testRemoveAll(): void
    {
        $session = new Session();
        $session->open();

        $session->set('name1', 'value1');
        $session->set('name2', 'value2');
        $this->assertEquals('value1', $session->get('name1'));
        $this->assertEquals('value2', $session->get('name2'));

        $session->removeAll();
        $this->assertNull($session->get('name1'));
        $this->assertNull($session->get('name2'));

        $session->destroy();
    }

    public static function setCacheLimiterDataProvider(): array
    {
        return [
            ['no-cache'],
            ['public'],
            ['private'],
            ['private_no_expire'],
        ];
    }

    /**
     * @dataProvider setCacheLimiterDataProvider
     *
     * @param string $cacheLimiter
     */
    public function testSetCacheLimiter(string $cacheLimiter): void
    {
        $session = new Session();
        $session->open();

        $session->setCacheLimiter($cacheLimiter);
        $this->assertEquals($cacheLimiter, $session->getCacheLimiter());

        $session->destroy();
    }

    public static function setCookieParamsDataProvider(): array
    {
        return [
            [
                [
                    'lifetime' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => false,
                    'samesite' => '',
                ]
            ],
            [
                [
                    'lifetime' => 3600,
                    'path' => '/path',
                    'domain' => 'example.com',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]
            ],
        ];
    }

    /**
     * @dataProvider setCookieParamsDataProvider
     *
     * @param array $cookieParams
     */
    public function testSetCookieParams(array $cookieParams): void
    {
        $session = new Session();
        $session->open();

        $session->setCookieParams($cookieParams);
        $this->assertSame($cookieParams, $session->getCookieParams());

        $session->destroy();
    }

    public function testSetHasSessionId(): void
    {
        $this->mockWebApplication();

        $session = new Session();
        $session->open();

        $this->assertFalse($session->getHasSessionID());

        $session->setHasSessionID(false);
        $this->assertFalse($session->getHasSessionID());

        $session->setHasSessionID(true);
        $this->assertTrue($session->getHasSessionID());

        $session->destroy();

        $this->destroyApplication();
    }

    /**
     * Test set name. Also check set name twice and after open.
     */
    public function testSetName(): void
    {
        $session = new Session();

        $session->setName('oldName');
        $this->assertEquals('oldName', $session->getName());

        $session->open();
        $session->setName('newName');
        $this->assertEquals('newName', $session->getName());

        $session->destroy();
    }

    public function testSetSavePath(): void
    {
        $session = new Session();

        if (!is_dir(dirname(__DIR__, 3) . '/runtime/sessions')) {
            mkdir(dirname(__DIR__, 3) . '/runtime/sessions', 0777, true);
        }

        $session->setSavePath(dirname(__DIR__, 3) . '/runtime/sessions');
        $this->assertSame(dirname(__DIR__, 3) . '/runtime/sessions', $session->getSavePath());

        $session->setSavePath(dirname(__DIR__, 3) . '/runtime');
        $session->open();

        $this->assertSame(dirname(__DIR__, 3) . '/runtime', $session->getSavePath());

        $session->destroy();
    }

    public function testSetSavePathWithInvalidPath(): void
    {
        $session = new Session();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Session save path is not a valid directory: /non-existing-directory');

        $session->setSavePath('/non-existing-directory');
    }

    public function testInitUseStrictMode(): void
    {
        $this->initStrictModeTest(Session::class);
    }

    public function testUseStrictMode(): void
    {
        //Manual garbage collection since native storage module might not support removing data via Session::destroy()
        $sessionSavePath = session_save_path() ?: sys_get_temp_dir();

        // Only perform garbage collection if "N argument" is not used,
        // see https://www.php.net/manual/en/session.configuration.php#ini.session.save-path
        if (strpos($sessionSavePath, ';') === false) {
            foreach (['non-existing-non-strict', 'non-existing-strict'] as $sessionId) {
                @unlink($sessionSavePath . '/sess_' . $sessionId);
            }
        }

        $this->useStrictModeTest(Session::class);
    }

    public function testSessionIterator(): void
    {
        $this->sessionIterator(Session::class);
    }

    public function testAddFlash(): void
    {
        $this->add(Session::class);
    }

    public function testAddToExistingArrayFlash(): void
    {
        $this->addToExistingArray(Session::class);
    }

    public function testAddValueToExistingNonArrayFlash(): void
    {
        $this->addValueToExistingNonArray(Session::class);
    }

    public function testAddWithRemoveFlash(): void
    {
        $this->addWithRemove(Session::class);
    }

    public function testGetFlash(): void
    {
        $this->get(Session::class);
    }

    public function testGellAllFlash(): void
    {
        $this->getAll(Session::class);
    }

    public function testGetWithRemoveFlash(): void
    {
        $this->getWithRemove(Session::class);
    }

    public function testHasFlash(): void
    {
        $this->has(Session::class);
    }

    public function testRemoveFlash(): void
    {
        $this->remove(Session::class);
    }

    public function testRemoveAllFlash(): void
    {
        $this->removeAll(Session::class);
    }

    public function testSetFlash(): void
    {
        $this->set(Session::class);
    }

    public function testUpdateCountersWithNonArrayFlashes(): void
    {
        $this->updateCountersWithNonArrayFlashes(Session::class);
    }

    public function testUpdateCountersWithNonArrayCounters(): void
    {
        $this->updateCountersWithNonArrayCounters(Session::class);
    }
}
