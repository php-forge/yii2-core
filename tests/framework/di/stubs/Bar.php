<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

final class Bar extends BaseObject
{
    public $qux;

    public function __construct(QuxInterface $qux, $config = [])
    {
        $this->qux = $qux;
        parent::__construct($config);
    }
}
