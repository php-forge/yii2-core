<?php

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

final class Car extends BaseObject
{
    public $color;
    public $name;

    public function __construct($color, $name)
    {
        $this->color = $color;
        $this->name = $name;
    }
}
