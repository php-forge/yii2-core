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
        $this->session = null;

        parent::tearDown();

        $this->destroyApplication();
    }

    public function testCount(): void
    {
        $this->session->open();

        $this->assertSame(0, $this->session->count);

        $this->session->set('name', 'value');

        $this->assertSame(1, $this->session->count());

        $this->session->destroy();
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
        $this->session->open();

        $this->assertSame(0, $this->session->getCount());

        $this->session->set('name', 'value');

        $this->assertSame(1, $this->session->getCount());

        $this->session->destroy();
    }

    public function testHas(): void
    {
        $this->session->open();

        $this->assertFalse($this->session->has('name'));

        $this->session->set('name', 'value');

        $this->assertTrue($this->session->has('name'));

        $this->session->destroy();
    }

    public function testOffsetExists(): void
    {
        $this->session->open();

        $this->assertFalse(isset($this->session['name']));

        $this->session['name'] = 'value';

        $this->assertTrue(isset($this->session['name']));

        $this->session->destroy();
    }

    public function testOffsetGet(): void
    {
        $this->session->open();

        $this->assertNull($this->session['name']);

        $this->session['name'] = 'value';

        $this->assertSame('value', $this->session['name']);

        $this->session->destroy();
    }

    public function testOffsetSet(): void
    {
        $this->session->open();

        $this->session['name'] = 'value';

        $this->assertSame('value', $this->session['name']);

        $this->session->destroy();
    }

    public function testOffsetUnset(): void
    {
        $this->session->open();

        $this->session['name'] = 'value';

        $this->assertSame('value', $this->session['name']);

        unset($this->session['name']);

        $this->assertNull($this->session['name']);

        $this->session->destroy();
    }

    public function testRegenerateID(): void
    {
        $this->session->open();

        $oldSessionId = $this->session->getId();

        $this->session->regenerateID();

        $newSessionId = $this->session->getId();

        $this->assertNotSame($oldSessionId, $newSessionId);

        $this->session->destroy();
    }

    public function testRemove(): void
    {
        $this->session->open();

        $this->session->set('name', 'value');

        $this->assertSame('value', $this->session->get('name'));

        $this->session->remove('name');

        $this->assertNull($this->session->get('name'));

        $this->session->destroy();
    }

    public function testRemoveAll(): void
    {
        $this->session->open();

        $this->session->set('name1', 'value1');
        $this->session->set('name2', 'value2');

        $this->assertSame('value1', $this->session->get('name1'));
        $this->assertSame('value2', $this->session->get('name2'));

        $this->session->removeAll();

        $this->assertNull($this->session->get('name1'));
        $this->assertNull($this->session->get('name2'));

        $this->session->destroy();
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

        $this->session->destroy();
    }

    /**
     * @dataProvider \yiiunit\framework\web\session\provider\SessionProvider::setCookieParamsDataProvider
     *
     * @param array $cookieParams
     */
    public function testSetCookieParams(array $cookieParams): void
    {
        $this->session->open();

        $this->session->setCookieParams($cookieParams);

        $this->assertSame($cookieParams, $this->session->getCookieParams());

        $this->session->destroy();
    }

    public function testSetHasSessionId(): void
    {
        $this->session->open();

        $this->assertFalse($this->session->getHasSessionID());

        $this->session->setHasSessionID(false);

        $this->assertFalse($this->session->getHasSessionID());

        $this->session->setHasSessionID(true);

        $this->assertTrue($this->session->getHasSessionID());

        $this->session->destroy();

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

        $this->session->destroy();
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

        $this->session->destroy();
    }

    public function testSetSavePathWithInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Session save path is not a valid directory: /non-existing-directory');

        $this->session->setSavePath('/non-existing-directory');
    }

    public function testInitUseStrictMode(): void
    {
        $this->session->useStrictMode = false;

        $this->assertSame(false, $this->session->getUseStrictMode());

        $this->session->useStrictMode = true;

        $this->assertEquals(true, $this->session->getUseStrictMode());
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

        $this->session->destroy('strict_mode_test');

    }

    public function testSessionIterator(): void
    {
        $this->session->open();

        $this->session->set('key1', 'value1');

        $iterator = $this->session->getIterator();

        $this->assertInstanceOf(\Iterator::class, $iterator);
        $this->assertSame('key1', $iterator->key());
        $this->assertSame('value1', $iterator->current());

        $iterator->next();

        $this->assertNull($iterator->key());
        $this->assertNull($iterator->current());
    }

    public function testAddFlash(): void
    {
        $this->session->addFlash('key', 'value');

        $this->assertSame(['value'], $this->session->getFlash('key'));
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

    public function testHasFlash(): void
    {
        $this->assertFalse($this->session->hasFlash('key'));

        $this->session->addFlash('key', 'value');

        $this->assertTrue($this->session->hasFlash('key'));

        $this->session->removeFlash('key');
    }

    public function testRemoveFlash(): void
    {
        $this->session->addFlash('key', 'value');

        $this->assertSame(['value'], $this->session->getFlash('key'));

        $this->session->removeFlash('key');

        $this->assertNull($this->session->getFlash('key'));
    }

    public function testRemoveAllFlash(): void
    {
        $this->session->addFlash('key1', 'value1');
        $this->session->addFlash('key2', 'value2');

        $this->assertSame(['key1' => ['value1'], 'key2' => ['value2']], $this->session->getAllFlashes());

        $this->session->removeAllFlashes();

        $this->assertSame([], $this->session->getAllFlashes());
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
}
