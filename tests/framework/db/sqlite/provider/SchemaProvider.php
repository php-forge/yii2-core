<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\provider;

final class SchemaProvider extends \yiiunit\framework\db\provider\AbstractSchemaProvider
{
    public static function resetAutoIncrementPK(): array
    {
        $rows = parent::resetAutoIncrementPK();

        $rows['value with zero'] = [
            '{{%T_reset_autoincrement_pk}}',
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
