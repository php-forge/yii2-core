<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\provider\types;

use yii\db\mssql\ColumnSchemaBuilder;
use yii\db\Schema;
use yiiunit\support\TestHelper;

final class BigAutoIncrementProvider extends \yiiunit\framework\db\provider\types\AbstractBigAutoIncrementProvider
{
    public static function command(): array
    {
        return [
            // default
            [
                Schema::TYPE_BIGAUTO,
                'bigint IDENTITY',
                null,
                'bigint',
                '2',
            ],
            [
                Schema::TYPE_BIGAUTO . '(1)',
                'bigint IDENTITY',
                null,
                'bigint',
                '2',
            ],
            [
                Schema::TYPE_BIGAUTO . '(0,0)',
                'bigint IDENTITY(0,1)',
                null,
                'bigint',
                '1',
            ],
            [
                Schema::TYPE_BIGAUTO . '(1,1)',
                'bigint IDENTITY(1,1)',
                null,
                'bigint',
                '2',
            ],
            [
                Schema::TYPE_BIGAUTO . '(2,3)',
                'bigint IDENTITY(2,3)',
                null,
                'bigint',
                '5',
            ],
            [
                Schema::TYPE_BIGAUTO . '(-10,1)',
                'bigint IDENTITY(-10,1)',
                null,
                'bigint',
                '-9',
            ],
            // builder
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO),
                'bigint IDENTITY',
                null,
                'bigint',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO, [1]),
                'bigint IDENTITY',
                null,
                'bigint',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO, [0, 0]),
                'bigint IDENTITY(0,1)',
                null,
                'bigint',
                '1',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO, [1, 1]),
                'bigint IDENTITY(1,1)',
                null,
                'bigint',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO, [2, 3]),
                'bigint IDENTITY(2,3)',
                null,
                'bigint',
                '5',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO, [-10, 1]),
                'bigint IDENTITY(-10,1)',
                null,
                'bigint',
                '-9',
            ],
            // builder generator
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigAutoIncrement(),
                'bigint IDENTITY',
                null,
                'bigint',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigAutoIncrement(1),
                'bigint IDENTITY',
                null,
                'bigint',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigAutoIncrement(0, 0),
                'bigint IDENTITY(0,1)',
                null,
                'bigint',
                '1',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigAutoIncrement(1, 1),
                'bigint IDENTITY(1,1)',
                null,
                'bigint',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigAutoIncrement(2, 3),
                'bigint IDENTITY(2,3)',
                null,
                'bigint',
                '5',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigAutoIncrement(-10, 1),
                'bigint IDENTITY(-10,1)',
                null,
                'bigint',
                '-9',
            ],
        ];
    }

    public static function queryBuilder(): array
    {
        $expected = [
            'bigauto' => [
                1 => 'bigauto',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(),
                3 => 'bigint IDENTITY',
            ],
            'bigauto(1)' => [
                1 => 'bigauto',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(1),
                3 => 'bigint IDENTITY',
            ],
            'bigauto(0,0)' => [
                1 => 'bigauto(0,1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(0, 0),
                3 => 'bigint IDENTITY(0,1)',
            ],
            'bigauto(1,1)' => [
                1 => 'bigauto(1,1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(1, 1),
                3 => 'bigint IDENTITY(1,1)',
            ],
            'bigauto(2,3)' => [
                1 => 'bigauto(2,3)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(2, 3),
                3 => 'bigint IDENTITY(2,3)',
            ],
            'bigauto(-10,1)' => [
                Schema::TYPE_BIGAUTO . '(-10,1)',
                'bigauto(-10,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(-10, 1),
                'bigint IDENTITY(-10,1)',
            ],
        ];

        $types = parent::queryBuilder();

        return TestHelper::addExpected($expected, $types);
    }
}
