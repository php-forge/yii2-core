<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\provider;

final class SchemaProvider extends \yiiunit\framework\db\provider\AbstractSchemaProvider
{
    public static function resetSequence(): array
    {
        $rows = parent::resetSequence();

        $rows['value with zero'] = [
            '{{%reset_sequence}}',
            [
                ['name' => 'name1'],
                ['name' => 'name2'],
                ['name' => 'name3'],
            ],
            [0, 1, 2],
            0,
        ];

        $rows['value negative'] = [
            '{{%reset_sequence}}',
            [
                ['name' => 'name1'],
                ['name' => 'name2'],
                ['name' => 'name3'],
            ],
            [-5, -4, -3],
            -5,
        ];

        return $rows;
    }
}
