<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use yii\web\session\Session;

trait FlashTestTrait
{
    public function add(string $class): void
    {
        /** @var Session $class */
        $session = new $class();

        $session->addFlash('key', 'value');

        $this->assertSame(['value'], $session->getFlash('key'));
    }

    public function addWithRemove(string $class): void
    {
        /** @var Session $class */
        $session = new $class();

        $session->addFlash('key', 'value', true);

        $this->assertSame(['value'], $session->getFlash('key'));
        $this->assertSame(null, $session->getFlash('key'));
    }

    public function get(string $class): void
    {
        /** @var Session $class */
        $session = new $class();

        $this->assertNull($session->getFlash('key'));
        $this->assertFalse($session->get('key', false));
    }

    public function getAll(string $class): void
    {
        /** @var Session $class */
        $session = new $class();

        $session->addFlash('key1', 'value1');
        $session->addFlash('key2', 'value2');

        $this->assertSame(['key1' => ['value1'], 'key2' => ['value2']], $session->getAllFlashes());
    }

    public function getWithRemove(string $class): void
    {
        /** @var Session $class */
        $session = new $class();

        $session->addFlash('key', 'value', true);

        $this->assertSame(['value'], $session->getFlash('key', null, true));
        $this->assertNull($session->getFlash('key'));
    }

    public function has(string $class): void
    {
        /** @var Session $class */
        $session = new $class();

        $this->assertFalse($session->hasFlash('key'));

        $session->addFlash('key', 'value');

        $this->assertTrue($session->hasFlash('key'));
    }

    public function remove(string $class): void
    {
        /** @var Session $class */
        $session = new $class();

        $session->addFlash('key', 'value');

        $this->assertSame(['value'], $session->getFlash('key'));

        $session->removeFlash('key');

        $this->assertNull($session->getFlash('key'));
    }

    public function removeAll(string $class): void
    {
        /** @var Session $class */
        $session = new $class();

        $session->addFlash('key1', 'value1');
        $session->addFlash('key2', 'value2');

        $this->assertSame(['key1' => ['value1'], 'key2' => ['value2']], $session->getAllFlashes());

        $session->removeAllFlashes();

        $this->assertSame([], $session->getAllFlashes());
    }

    public function set(string $class): void
    {
        /** @var Session $class */
        $session = new $class();

        $session->setFlash('key');

        $this->assertSame(['key' => true], $session->getAllFlashes());

        $session->setFlash('key', 'value');

        $this->assertSame(['key' => 'value'], $session->getAllFlashes());

        $session->setFlash('key', 'value', true);

        $this->assertSame(['key' => 'value'], $session->getAllFlashes());
        $this->assertNull($session->getFlash('key'));
    }
}
