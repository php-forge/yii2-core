<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider;

use yii\db\Schema;

class AbstractColumnTypeProvider
{
    public static function autoIncrement(): array
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

    public static function bigAutoIncrement(): array
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

    public static function bigPrimaryKey(): array
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

    public static function primaryKey(): array
    {
        return [
            'pk' => [
                Schema::TYPE_PK,
            ],
            'pk(1)' => [
                Schema::TYPE_PK . '(1)',
            ],
            'pk(0,0)' => [
                Schema::TYPE_PK . '(0,0)',
            ],
            'pk(1,1)' => [
                Schema::TYPE_PK . '(1,1)',
            ],
            'pk(2,3)' => [
                Schema::TYPE_PK . '(2,3)',
            ],
        ];
    }
}
