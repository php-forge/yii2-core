<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider\types;

use yii\db\Schema;

abstract class AbstractPrimaryKeyProvider
{
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
