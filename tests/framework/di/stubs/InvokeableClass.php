<?php

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

class InvokeableClass extends BaseObject
{
    public function __invoke()
    {
        return 'invoked';
    }
}
