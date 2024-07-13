<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\di\Container;

final class QuxFactory extends \yii\base\BaseObject
{
    public static function create(Container $container)
    {
        return new Qux(42);
    }
}
