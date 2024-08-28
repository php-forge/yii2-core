<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider\types;

use yii\db\Schema;

abstract class AbstractBigPrimaryKeyProvider
{
    public static function queryBuilder(): array
    {
        return [
            'bigpk' => [
                Schema::TYPE_BIGPK,
            ],
            'bigpk(1)' => [
                Schema::TYPE_BIGPK . '(1)',
            ],
            'bigpk(0,0)' => [
                Schema::TYPE_BIGPK . '(0,0)',
            ],
            'bigpk(1,1)' => [
                Schema::TYPE_BIGPK . '(1,1)',
            ],
            'bigpk(2,3)' => [
                Schema::TYPE_BIGPK . '(2,3)',
            ],
        ];
    }
}
