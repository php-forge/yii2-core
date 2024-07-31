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
}
