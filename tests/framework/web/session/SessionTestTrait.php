<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use yii\web\Session;

trait SessionTestTrait
{
    public function initStrictModeTest(string $class): void
    {
        /** @var Session $session */
        $session = new $class();

        $session->useStrictMode = false;
        $this->assertEquals(false, $session->getUseStrictMode());

        $session->useStrictMode = true;
        $this->assertEquals(true, $session->getUseStrictMode());
    }

    /**
     * @param string $class
     */
    protected function useStrictModeTest(string $class): void
    {
        /** @var Session $session */
        $session = new $class();

        //non-strict-mode test
        $session->useStrictMode = false;
        $session->close();
        $session->destroy('non-existing-non-strict');
        $session->setId('non-existing-non-strict');
        $session->open();
        $this->assertEquals('non-existing-non-strict', $session->getId());
        $session->close();

        //strict-mode test
        $session->useStrictMode = true;
        $session->close();
        $session->destroy('non-existing-strict');
        $session->setId('non-existing-strict');
        $session->open();
        $id = $session->getId();
        $this->assertNotEquals('non-existing-strict', $id);
        $session->set('strict_mode_test', 'session data');
        $session->close();

        //Ensure session was not stored under forced id
        $session->setId('non-existing-strict');
        $session->open();
        $this->assertNotEquals('session data', $session->get('strict_mode_test'));
        $session->close();

        //Ensure session can be accessed with the new (and thus existing) id.
        $session->setId($id);
        $session->open();
        $this->assertNotEmpty($id);
        $this->assertEquals($id, $session->getId());
        $this->assertEquals('session data', $session->get('strict_mode_test'));
        $session->close();
    }
}
