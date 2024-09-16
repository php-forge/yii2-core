<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider;

abstract class AbstractSchemaProvider
{
    public static function resetSequence(): array
    {
        return [
            'no value' => [
                '{{%reset_sequence}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [1, 2, 3],
            ],
            'null value' => [
                '{{%reset_sequence}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [1, 2, 3],
                null,
            ],
            'value' => [
                '{{%reset_sequence}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [5, 6, 7],
                5,
            ],
            'value with zero' => [
                '{{%reset_sequence}}',
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