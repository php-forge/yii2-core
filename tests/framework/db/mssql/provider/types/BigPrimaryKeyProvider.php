<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\provider\types;

use yii\db\mssql\ColumnSchemaBuilder;
use yii\db\Schema;
use yiiunit\support\TestHelper;

final class BigPrimaryKeyProvider extends \yiiunit\framework\db\provider\types\AbstractBigPrimaryKeyProvider
{
    public static function builder(): array
    {
        $expected = [
            'bigpk' => [
                1 => 'bigpk',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(),
                3 => 'bigint IDENTITY PRIMARY KEY',
            ],
            'bigpk(1)' => [
                1 => 'bigpk',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(1),
                3 => 'bigint IDENTITY PRIMARY KEY',
            ],
            'bigpk(0,0)' => [
                1 => 'bigpk(0,1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(0, 0),
                3 => 'bigint IDENTITY(0,1) PRIMARY KEY',
            ],
            'bigpk(1,1)' => [
                1 => 'bigpk(1,1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(1, 1),
                3 => 'bigint IDENTITY(1,1) PRIMARY KEY',
            ],
            'bigpk(2,3)' => [
                1 => 'bigpk(2,3)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(2, 3),
                3 => 'bigint IDENTITY(2,3) PRIMARY KEY',
            ],
            'bigpk(-10,1)' => [
                Schema::TYPE_BIGPK . '(-10,1)',
                'bigpk(-10,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(-10, 1),
                'bigint IDENTITY(-10,1) PRIMARY KEY',
            ],
        ];

        $types = parent::bigPrimaryKey();

        return TestHelper::addExpected($expected, $types);
    }

    public static function schema(): array
    {
        return [
            // schema
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGPK),
                'bigint IDENTITY PRIMARY KEY',
                true,
                'bigint',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGPK, [0, 0]),
                'bigint IDENTITY(0,1) PRIMARY KEY',
                true,
                'bigint',
                '1',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGPK, [-10, 2]),
                'bigint IDENTITY(-10,2) PRIMARY KEY',
                true,
                'bigint',
                '-8',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGPK, [2, 3]),
                'bigint IDENTITY(2,3) PRIMARY KEY',
                true,
                'bigint',
                '5',
            ],
            // builder generator
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigPrimaryKey(),
                'bigint IDENTITY PRIMARY KEY',
                true,
                'bigint',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigPrimaryKey(0, 0),
                'bigint IDENTITY(0,1) PRIMARY KEY',
                true,
                'bigint',
                '1',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigPrimaryKey(-10, 2),
                'bigint IDENTITY(-10,2) PRIMARY KEY',
                true,
                'bigint',
                '-8',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigPrimaryKey(2, 3),
                'bigint IDENTITY(2,3) PRIMARY KEY',
                true,
                'bigint',
                '5',
            ],
            // raw sql
            [
                'bigint IDENTITY PRIMARY KEY',
                'bigint IDENTITY PRIMARY KEY',
                true,
                'bigint',
                '2',
            ],
            [
                'bigint IDENTITY(0,0) PRIMARY KEY',
                'bigint IDENTITY(0,1) PRIMARY KEY',
                true,
                'bigint',
                '1',
            ],
            [
                'bigint IDENTITY(-10,2) PRIMARY KEY',
                'bigint IDENTITY(-10,2) PRIMARY KEY',
                true,
                'bigint',
                '-8',
            ],
            [
                'bigint IDENTITY(2,3) PRIMARY KEY',
                'bigint IDENTITY(2,3) PRIMARY KEY',
                true,
                'bigint',
                '5',
            ],
        ];
    }

    public static function raw(): array
    {
        return [
            [
                'bigint IDENTITY PRIMARY KEY',
                'bigpk',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(),
            ],
            [
                'bigint IDENTITY(1) PRIMARY KEY',
                'bigpk',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(1),
                'bigint IDENTITY PRIMARY KEY',
            ],
            [
                'bigint IDENTITY(0,0) PRIMARY KEY',
                'bigpk(0,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(0, 0),
                'bigint IDENTITY(0,1) PRIMARY KEY',
            ],
            [
                'bigint IDENTITY(1,1) PRIMARY KEY',
                'bigpk(1,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(1, 1),
            ],
            [
                'bigint IDENTITY(2,3) PRIMARY KEY',
                'bigpk(2,3)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(2, 3),
            ],
            [
                'bigint IDENTITY(-10,1) PRIMARY KEY',
                'bigpk(-10,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(-10, 1),
                'bigint IDENTITY(-10,1) PRIMARY KEY',
            ],
        ];
    }
}
