<?php

declare(strict_types=1);

namespace yiiunit\framework\di;

use Yii;
use yii\di\{Container, NotInstantiableException};
use yiiunit\TestCase;

/**
 * @group di
 */
class NotInstatiableExceptionTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Yii::$container = new Container();
    }

    public function testGetName(): void
    {
        $exception = new NotInstantiableException();

        $this->assertSame('Not Instantiable', $exception->getName());
    }
}
