<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use Yii;
use yii\base\InvalidArgumentException;
use yii\log\Logger;
use yii\web\session\Session;
use yiiunit\TestCase;

/**
 * Class SessionExceptionTest.
 *
 * @group web
 * @group session
 */
class SessionExceptionTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    public function testOpenFailure(): void
    {
        $this
            ->getFunctionMock('yii\web\session', 'error_get_last')
            ->expects($this->once())
            ->willReturn(['message' => 'Failed to start session.']);

        /** @var Session $session */
        $session = $this
            ->getMockBuilder(Session::class)
            ->onlyMethods(['getIsActive'])
            ->getMock();
        $session->method('getIsActive')->willReturn(false);

        Yii::getLogger()->flush();

        $session->open();

        $this->assertCount(1, Yii::getLogger()->messages);
        $this->assertSame(Logger::LEVEL_ERROR, Yii::getLogger()->messages[0][1]);
        $this->assertSame('Failed to start session.', Yii::getLogger()->messages[0][0]);
        $this->assertSame('yii\web\session\Session::open', Yii::getLogger()->messages[0][2]);

        $session->close();
    }

    public function testUnfreezeFailure(): void
    {
        $this
            ->getFunctionMock('yii\web\session', 'error_get_last')
            ->expects($this->once())
            ->willReturn(['message' => 'Failed to unfreeze session.']);

        $_SESSION = ['test' => 'value'];

        Yii::getLogger()->flush();

        /** @var Session $session */
        $session = $this->getMockBuilder(Session::class)->onlyMethods(['getIsActive'])->getMock();
        $session->method('getIsActive')->will($this->onConsecutiveCalls(true, true, false, false));

        $session->setName('test');

        $this->assertStringContainsString('Failed to unfreeze session.', Yii::getLogger()->messages[1][0]);

        $session->close();

        unset($_SESSION);
    }

    public function testSetCookieParamsFailure(): void
    {
        /** @var Session $session */
        $session = $this->getMockBuilder(Session::class)->onlyMethods(['getCookieParams', 'getIsActive'])->getMock();
        $session->method('getCookieParams')->willReturn(['test' => 'value']);
        $session->method('getIsActive')->willReturn(false);

        $this->assertSame(['test' => 'value'], $session->getCookieParams());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Please make sure cookieParams contains these elements: lifetime, path, domain, secure and httponly.'
        );

        $session->open();
    }
}
