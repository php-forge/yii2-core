<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\provider;

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
            [1, 2, 3],
            0,
        ];

        return $rows;
    }
}
