<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
final class Foo extends BaseObject
{
    public $bar;

    public function __construct(Bar $bar, $config = [])
    {
        $this->bar = $bar;
        parent::__construct($config);
    }
}
