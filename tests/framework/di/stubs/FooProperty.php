<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

final class FooProperty extends BaseObject
{
    /**
     * @var BarSetter
     */
    public $bar;
}
