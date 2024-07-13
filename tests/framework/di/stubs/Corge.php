<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

final class Corge extends BaseObject
{
    public $map;

    public function __construct(array $map, $config = [])
    {
        $this->map = $map;
        parent::__construct($config);
    }
}
