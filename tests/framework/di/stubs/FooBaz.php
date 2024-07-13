<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class FooBaz extends \yii\base\BaseObject
{
    public $fooDependent = [];

    public function init()
    {
        // default config usually used by Yii
        $dependentConfig = array_merge(['class' => FooDependent::className()], $this->fooDependent);
        $this->fooDependent = \Yii::createObject($dependentConfig);
    }
}

class FooDependent extends \yii\base\BaseObject
{
}

class FooDependentSubclass extends FooDependent
{
}
