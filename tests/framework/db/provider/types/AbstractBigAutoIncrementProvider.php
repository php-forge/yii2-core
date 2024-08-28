<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider\types;

use yii\db\Schema;

abstract class AbstractBigAutoIncrementProvider
{
    public static function queryBuilder(): array
    {
        return [
            'bigauto' => [
                Schema::TYPE_BIGAUTO,
            ],
            'bigauto(1)' => [
                Schema::TYPE_BIGAUTO . '(1)',
            ],
            'bigauto(0,0)' => [
                Schema::TYPE_BIGAUTO . '(0,0)',
            ],
            'bigauto(1,1)' => [
                Schema::TYPE_BIGAUTO . '(1,1)',
            ],
            'bigauto(2,3)' => [
                Schema::TYPE_BIGAUTO . '(2,3)',
            ],
        ];
    }
}
