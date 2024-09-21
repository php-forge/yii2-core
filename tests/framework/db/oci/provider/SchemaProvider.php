<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\provider;

final class SchemaProvider extends \yiiunit\framework\db\provider\AbstractSchemaProvider
{
    public static function resetAutoIncrementPK(): array
    {
        $rows = parent::resetAutoIncrementPK();

        $rows['value with zero'] = [
            '{{%reset_autoincrement_pk}}',
            [
                ['name' => 'name1'],
                ['name' => 'name2'],
                ['name' => 'name3'],
            ],
            [0, 1, 2],
            0,
        ];

        $rows['value negative'] = [
            '{{%reset_autoincrement_pk}}',
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
