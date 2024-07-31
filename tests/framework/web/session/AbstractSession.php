<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use Yii;
use yii\base\InvalidArgumentException;
use yii\web\session\Session;
use yiiunit\TestCase;

abstract class AbstractSession extends TestCase
{
    protected Session|null $session = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = Yii::$app->getSession();
    }

    protected function tearDown(): void
    {
        $this->session->destroy();

        $this->session = null;

        parent::tearDown();

        $this->destroyApplication();
    }

    public function testAddFlash(): void
    {
        $this->session->addFlash('key', 'value');

        $this->assertSame(['value'], $this->session->getFlash('key'));

        $this->session->removeFlash('key');
    }

    public function testAddToExistingArrayFlash(): void
    {
        $this->session->addFlash('key', 'value1', false);
        $this->session->addFlash('key', 'value2', false);

        $this->assertSame(['value1', 'value2'], $this->session->getFlash('key'));

        $this->session->removeFlash('key');
    }

    public function testAddValueToExistingNonArrayFlash(): void
    {
        $this->session->setFlash('testKey', 'initialValue');
        $this->session->addFlash('testKey', 'newValue');

        $result = $this->session->getFlash('testKey');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('initialValue', $result[0]);
        $this->assertSame('newValue', $result[1]);

        $this->session->removeFlash('testKey');
    }

    public function testAddWithRemoveFlash(): void
    {
        $this->session->addFlash('key', 'value', true);

        $this->assertSame(['value'], $this->session->getFlash('key'));
        $this->assertSame(null, $this->session->getFlash('key'));

        $this->session->removeFlash('key');
    }

    public function testCount(): void
    {
        $this->assertSame(0, $this->session->count());

        $this->session->set('name', 'value');

        $this->assertSame(1, $this->session->count());
    }

    public function testDestroySessionId(): void
    {
        $this->session->open();

        $oldSessionId = @session_id();

        $this->assertNotEmpty($oldSessionId);

        $this->session->destroy();

        $newSessionId = @session_id();

        $this->assertNotEmpty($newSessionId);
        $this->assertSame($oldSessionId, $newSessionId);
    }

    public function testGetCount(): void
    {
        $this->assertSame(0, $this->session->getCount());

        $this->session->set('name', 'value');

        $this->assertSame(1, $this->session->getCount());
    }

    public function testGetFlash(): void
    {
        $this->assertNull($this->session->getFlash('key'));
        $this->assertFalse($this->session->get('key', false));
    }

    public function testGellAllFlash(): void
    {
        $this->session->addFlash('key1', 'value1');
        $this->session->addFlash('key2', 'value2');

        $this->assertSame(['key1' => ['value1'], 'key2' => ['value2']], $this->session->getAllFlashes());

        $this->session->removeAllFlashes();
    }

    public function testGetWithRemoveFlash(): void
    {
        $this->session->addFlash('key', 'value', true);

        $this->assertSame(['value'], $this->session->getFlash('key', null, true));
        $this->assertNull($this->session->getFlash('key'));

    }

    public function testHas(): void
    {
        $this->assertFalse($this->session->has('name'));

        $this->session->set('name', 'value');

        $this->assertTrue($this->session->has('name'));

    }

    public function testHasFlash(): void
    {
        $this->assertFalse($this->session->hasFlash('key'));

        $this->session->addFlash('key', 'value');

        $this->assertTrue($this->session->hasFlash('key'));

        $this->session->removeFlash('key');
    }

    public function testIdIsSet(): void
    {
        $_COOKIE['PHPSESSID'] = 'test-id';

        $this->session->setName('PHPSESSID');
        $this->session->setUseCookies(true);

        $this->assertTrue($this->session->getUseCookies());
        $this->assertTrue($this->session->getHasSessionId());

        $this->session->setUseCookies(false);

        $this->assertFalse($this->session->getUseCookies());

        $_COOKIE = [];
    }

    public function testIdSetWithTransSid(): void
    {
        $_COOKIE['PHPSESSID'] = 'test-id';

        Yii::$app->request->setQueryParams(['PHPSESSID' => 'test-id']);

        $this->session->setName('PHPSESSID');
        $this->session->setUseCookies(false);
        $this->session->setUseTransparentSessionID(true);

        $this->assertTrue($this->session->getUseTransparentSessionID());
        $this->assertTrue($this->session->getHasSessionID());

        $this->session->setUseTransparentSessionID(false);

        $this->assertFalse($this->session->getUseTransparentSessionID());

        $_COOKIE = [];
    }

    public function testInitUseStrictMode(): void
    {
        $this->session->useStrictMode = false;

        $this->assertSame(false, $this->session->getUseStrictMode());

        $this->session->useStrictMode = true;

        $this->assertEquals(true, $this->session->getUseStrictMode());
    }

    public function testIterator(): void
    {
        $this->session->set('key1', 'value1');

        $iterator = $this->session->getIterator();

        $this->assertInstanceOf(\Iterator::class, $iterator);
        $this->assertSame('key1', $iterator->key());
        $this->assertSame('value1', $iterator->current());

        $iterator->next();

        $this->assertNull($iterator->key());
        $this->assertNull($iterator->current());
    }

    public function testIteratorValid()
    {
        $iterator = $this->session->getIterator();

        $this->assertFalse($iterator->valid());

        $this->session->set('key1', 'value1');
        $this->session->set('key2', 'value2');
        $this->session->set('key3', 'value3');

        $iterator = $this->session->getIterator();

        $this->assertTrue($iterator->valid());

        $count = 0;

        while ($iterator->valid()) {
            $count++;

            $iterator->next();
        }

        $this->assertEquals(3, $count);
        $this->assertFalse($iterator->valid());

        $this->session->removeAll();

        $iterator = $this->session->getIterator();

        $this->assertFalse($iterator->valid());
    }

    public function testOffsetExists(): void
    {
        $this->session->open();

        $this->assertFalse(isset($this->session['name']));

        $this->session['name'] = 'value';

        $this->assertTrue(isset($this->session['name']));
    }

    public function testOffsetGet(): void
    {
        $this->session->open();

        $this->assertNull($this->session['name']);

        $this->session['name'] = 'value';

        $this->assertSame('value', $this->session['name']);
    }

    public function testOffsetSet(): void
    {
        $this->session->open();

        $this->session['name'] = 'value';

        $this->assertSame('value', $this->session['name']);
    }

    public function testOffsetUnset(): void
    {
        $this->session->open();

        $this->session['name'] = 'value';

        $this->assertSame('value', $this->session['name']);

        unset($this->session['name']);

        $this->assertNull($this->session['name']);
    }

    /**
     * Test to prove that after Session::open changing session parameters will not throw exceptions and its values will
     * be changed as expected.
     */
    public function testParamsAfterSessionStart(): void
    {
        $this->session->open();

        $this->session->setUseCookies(true);

        $oldUseTransparentSession = $this->session->getUseTransparentSessionID();
        $this->session->setUseTransparentSessionID(true);
        $newUseTransparentSession = $this->session->getUseTransparentSessionID();

        $this->assertNotSame($oldUseTransparentSession, $newUseTransparentSession);
        $this->assertTrue($newUseTransparentSession);

        $this->session->setUseTransparentSessionID(false);
        $oldTimeout = $this->session->getTimeout();
        $this->session->setTimeout(600);
        $newTimeout = $this->session->getTimeout();

        $this->assertNotEquals($oldTimeout, $newTimeout);
        $this->assertSame(600, $newTimeout);

        $oldUseCookies = $this->session->getUseCookies();

        $this->session->setUseCookies(false);

        $newUseCookies = $this->session->getUseCookies();

        if (null !== $newUseCookies) {
            $this->assertNotSame($oldUseCookies, $newUseCookies);
            $this->assertFalse($newUseCookies);
        }

        $oldGcProbability = $this->session->getGCProbability();
        $this->session->setGCProbability(100);
        $newGcProbability = $this->session->getGCProbability();

        $this->assertNotEquals($oldGcProbability, $newGcProbability);
        $this->assertEquals(100, $newGcProbability);

        $this->session->setGCProbability($oldGcProbability);
    }

    public function testRegenerateID(): void
    {
        $this->session->open();

        $oldSessionId = $this->session->getId();

        $this->session->regenerateID();

        $newSessionId = $this->session->getId();

        $this->assertNotSame($oldSessionId, $newSessionId);
    }

    public function testRemove(): void
    {
        $this->session->set('name', 'value');

        $this->assertSame('value', $this->session->get('name'));

        $this->session->remove('name');

        $this->assertNull($this->session->get('name'));
    }

    public function testRemoveAll(): void
    {
        $this->session->set('name1', 'value1');
        $this->session->set('name2', 'value2');

        $this->assertSame('value1', $this->session->get('name1'));
        $this->assertSame('value2', $this->session->get('name2'));

        $this->session->removeAll();

        $this->assertNull($this->session->get('name1'));
        $this->assertNull($this->session->get('name2'));
    }

    public function testRemoveAllFlash(): void
    {
        $this->session->addFlash('key1', 'value1');
        $this->session->addFlash('key2', 'value2');

        $this->assertSame(['key1' => ['value1'], 'key2' => ['value2']], $this->session->getAllFlashes());

        $this->session->removeAllFlashes();

        $this->assertSame([], $this->session->getAllFlashes());
    }

    public function testRemoveFlash(): void
    {
        $this->session->addFlash('key', 'value');

        $this->assertSame(['value'], $this->session->getFlash('key'));

        $this->session->removeFlash('key');

        $this->assertNull($this->session->getFlash('key'));
    }

    /**
     * @dataProvider \yiiunit\framework\web\session\provider\SessionProvider::setCacheLimiterDataProvider
     *
     * @param string $cacheLimiter
     */
    public function testSetCacheLimiter(string $cacheLimiter): void
    {
        $this->session->open();

        $this->session->setCacheLimiter($cacheLimiter);

        $this->assertSame($cacheLimiter, $this->session->getCacheLimiter());
    }

    /**
     * @dataProvider \yiiunit\framework\web\session\provider\SessionProvider::setCookieParamsDataProvider
     *
     * @param array $cookieParams
     */
    public function testSetCookieParams(array $cookieParams): void
    {
        $this->session->setCookieParams($cookieParams);

        $this->assertSame($cookieParams, $this->session->getCookieParams());
    }

    public function testSetFlash(): void
    {
        $this->session->setFlash('key');

        $this->assertSame(['key' => true], $this->session->getAllFlashes());

        $this->session->setFlash('key', 'value');

        $this->assertSame(['key' => 'value'], $this->session->getAllFlashes());

        $this->session->setFlash('key', 'value', true);

        $this->assertSame(['key' => 'value'], $this->session->getAllFlashes());
        $this->assertNull($this->session->getFlash('key'));

        $this->session->removeFlash('key');
    }

    public function testSetHasSessionId(): void
    {
        $this->session->open();

        $this->assertFalse($this->session->getHasSessionID());

        $this->session->setHasSessionID(false);

        $this->assertFalse($this->session->getHasSessionID());

        $this->session->setHasSessionID(true);

        $this->assertTrue($this->session->getHasSessionID());
    }

    /**
     * Test set name. Also check set name twice and after open.
     */
    public function testSetName(): void
    {
        $this->session->setName('oldName');

        $this->assertSame('oldName', $this->session->getName());

        $this->session->open();
        $this->session->setName('newName');

        $this->assertSame('newName', $this->session->getName());
    }

    public function testSetSavePath(): void
    {
        if (!is_dir(dirname(__DIR__, 3) . '/runtime/sessions')) {
            mkdir(dirname(__DIR__, 3) . '/runtime/sessions', 0777, true);
        }

        $this->session->setSavePath(dirname(__DIR__, 3) . '/runtime/sessions');

        $this->assertSame(dirname(__DIR__, 3) . '/runtime/sessions', $this->session->getSavePath());

        $this->session->setSavePath(dirname(__DIR__, 3) . '/runtime');
        $this->session->open();

        $this->assertSame(dirname(__DIR__, 3) . '/runtime', $this->session->getSavePath());
    }

    public function testSetSavePathWithInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Session save path is not a valid directory: /non-existing-directory');

        $this->session->setSavePath('/non-existing-directory');
    }

    public function testUpdateCountersWithNonArrayFlashes(): void
    {
        $this->session->set('__flash', 'not an array');

        $result = $this->session->getAllFlashes();

        $this->assertIsArray($result);
        $this->assertEmpty($result);

        $flashes = $this->session->get('__flash');

        $this->assertIsArray($flashes);
        $this->assertArrayHasKey('__counters', $flashes);
        $this->assertIsArray($flashes['__counters']);
        $this->assertEmpty($flashes['__counters']);

        $this->session->remove('__flash');
    }

    public function testUpdateCountersWithNonArrayCounters(): void
    {
        $this->session->set('__flash', ['__counters' => 'not an array']);
        $this->session->addFlash('testKey', 'testValue');

        $flashes = $this->session->get('__flash');

        $this->assertIsArray($flashes);
        $this->assertArrayHasKey('__counters', $flashes);
        $this->assertIsArray($flashes['__counters']);
        $this->assertArrayHasKey('testKey', $flashes['__counters']);
        $this->assertEquals(-1, $flashes['__counters']['testKey']);

        $this->session->remove('__flash');
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

        //non-strict-mode test
        $this->session->useStrictMode = false;
        $this->session->destroy('non-existing-non-strict');
        $this->session->setId('non-existing-non-strict');
        $this->session->open();

        $this->assertSame('non-existing-non-strict', $this->session->getId());
        $this->session->close();

        //strict-mode test
        $this->session->useStrictMode = true;
        $this->session->destroy('non-existing-strict');
        $this->session->setId('non-existing-strict');
        $this->session->open();

        $id = $this->session->getId();

        $this->assertNotSame('non-existing-strict', $id);

        $this->session->set('strict_mode_test', 'session data');
        $this->session->close();

        //Ensure session was not stored under forced id
        $this->session->setId('non-existing-strict');
        $this->session->open();

        $this->assertNotSame('session data', $this->session->get('strict_mode_test'));
        $this->session->close();

        //Ensure session can be accessed with the new (and thus existing) id.
        $this->session->setId($id);
        $this->session->open();

        $this->assertNotEmpty($id);
        $this->assertSame($id, $this->session->getId());
        $this->assertSame('session data', $this->session->get('strict_mode_test'));
    }
}
