<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
final class Qux extends BaseObject implements QuxInterface
{
    public $a;

    public function __construct($a = 1, $config = [])
    {
        $this->a = $a;
        parent::__construct($config);
    }

    public function quxMethod()
    {
    }
}
