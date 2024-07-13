<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

final class UnionTypeNotNull extends BaseObject
{
    public function __construct(protected string|int|float|bool $value)
    {
    }
}
