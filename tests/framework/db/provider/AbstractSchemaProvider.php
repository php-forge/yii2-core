<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider;

abstract class AbstractSchemaProvider
{
    public static function resetAutoIncrementPK(): array
    {
        return [
            'no value' => [
                '{{%T_reset_autoincrement_pk}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [1, 2, 3],
            ],
            'null value' => [
                '{{%T_reset_autoincrement_pk}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [1, 2, 3],
                null,
            ],
            'value' => [
                '{{%T_reset_autoincrement_pk}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [5, 6, 7],
                5,
            ],
            'value with zero' => [
                '{{%T_reset_autoincrement_pk}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [1, 2, 3],
                1,
            ],
        ];
    }
}
