<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

use yii\base\BaseObject;

final class BarSetter extends BaseObject
{
    /**
     * @var QuxInterface
     */
    private $qux;

    /**
     * @return QuxInterface
     */
    public function getQux()
    {
        return $this->qux;
    }

    /**
     * @param mixed $qux
     */
    public function setQux(QuxInterface $qux)
    {
        $this->qux = $qux;
    }
}
