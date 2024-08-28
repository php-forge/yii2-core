<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider\types;

use yii\db\Schema;

abstract class AbstractAutoIncrementProvider
{
    public static function queryBuilder(): array
    {
        return [
            'auto' => [
                Schema::TYPE_AUTO,
            ],
            'auto(1)' => [
                Schema::TYPE_AUTO . '(1)',
            ],
            'auto(0,0)' => [
                Schema::TYPE_AUTO . '(0,0)',
            ],
            'auto(1,1)' => [
                Schema::TYPE_AUTO . '(1,1)',
            ],
            'auto(2,3)' => [
                Schema::TYPE_AUTO . '(2,3)',
            ],
        ];
    }
}
