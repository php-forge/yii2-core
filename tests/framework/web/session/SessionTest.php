<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use Yii;
use yii\log\Logger;
use yii\web\session\Session;

/**
 * @group web
 * @group session
 */
final class SessionTest extends AbstractSession
{
    protected function setUp(): void
    {
        $this->mockWebApplication();

        Yii::$app->set('session', ['class' => Session::class]);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->session->destroy();
        $this->session = null;

        parent::tearDown();
    }
}
