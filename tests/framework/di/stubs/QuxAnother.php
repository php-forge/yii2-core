<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

final class QuxAnother extends BaseObject implements QuxInterface
{
    public function quxMethod()
    {
    }
}
