<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class ServiceLocatorStub extends \yii\di\ServiceLocator
{
    public string|null $testProperty = 'test value';
}
