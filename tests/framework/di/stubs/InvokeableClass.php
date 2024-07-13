<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

final class InvokeableClass extends BaseObject
{
    public function __invoke()
    {
        return 'invoked';
    }
}
