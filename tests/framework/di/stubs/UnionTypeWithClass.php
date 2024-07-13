<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

final class UnionTypeWithClass extends BaseObject
{
    public function __construct(public string|Beta $value)
    {
    }
}
