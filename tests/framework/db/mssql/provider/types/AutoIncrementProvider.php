<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\provider\types;

use yii\db\mssql\ColumnSchemaBuilder;
use yii\db\Schema;
use yiiunit\support\TestHelper;

final class AutoIncrementProvider extends \yiiunit\framework\db\provider\types\AbstractAutoIncrementProvider
{
    public static function builder(): array
    {
        $expected = [
            'auto' => [
                1 => 'auto',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(),
                3 => 'int IDENTITY',
            ],
            'auto(1)' => [
                1 => 'auto',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(1),
                3 => 'int IDENTITY',
            ],
            'auto(0,0)' => [
                1 => 'auto(0,1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(0, 0),
                3 => 'int IDENTITY(0,1)',
            ],
            'auto(1,1)' => [
                1 => 'auto(1,1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(1, 1),
                3 => 'int IDENTITY(1,1)',
            ],
            'auto(2,3)' => [
                1 => 'auto(2,3)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(2, 3),
                3 => 'int IDENTITY(2,3)',
            ],
            'auto(-10,1)' => [
                Schema::TYPE_AUTO . '(-10,1)',
                'auto(-10,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(-10, 1),
                'int IDENTITY(-10,1)',
            ],
        ];

        $types = parent::autoIncrement();

        return TestHelper::addExpected($expected, $types);
    }

    public static function schema(): array
    {
        return [
            // schema
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO),
                'int IDENTITY',
                null,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO, [0, 0]),
                'int IDENTITY(0,1)',
                null,
                'integer',
                '1',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO, [-10, 2]),
                'int IDENTITY(-10,2)',
                null,
                'integer',
                '-8',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO, [2, 3]),
                'int IDENTITY(2,3)',
                null,
                'integer',
                '5',
            ],
            // builder generator
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->autoIncrement(),
                'int IDENTITY',
                null,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->autoIncrement(0, 0),
                'int IDENTITY(0,1)',
                null,
                'integer',
                '1',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->autoIncrement(-10, 2),
                'int IDENTITY(-10,2)',
                null,
                'integer',
                '-8',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->autoIncrement(2, 3),
                'int IDENTITY(2,3)',
                null,
                'integer',
                '5',
            ],
            // raw sql
            [
                'int IDENTITY',
                'int IDENTITY',
                null,
                'integer',
                '2',
            ],
            [
                'int IDENTITY(0,0)',
                'int IDENTITY(0,1)',
                null,
                'integer',
                '1',
            ],
            [
                'int IDENTITY(-10,2)',
                'int IDENTITY(-10,2)',
                null,
                'integer',
                '-8',
            ],
            [
                'int IDENTITY(2,3)',
                'int IDENTITY(2,3)',
                null,
                'integer',
                '5',
            ],

        ];
    }

    public static function raw(): array
    {
        return [
            [
                'int IDENTITY',
                'auto',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(),
            ],
            [
                'int IDENTITY(1)',
                'auto',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(1),
                'int IDENTITY',
            ],
            [
                'int IDENTITY(0,0)',
                'auto(0,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(0, 0),
                'int IDENTITY(0,1)',
            ],
            [
                'int IDENTITY(1,1)',
                'auto(1,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(1, 1),
            ],
            [
                'int IDENTITY(2,3)',
                'auto(2,3)',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(2, 3),
            ],
            [
                'int IDENTITY(-10,1)',
                'auto(-10,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(-10, 1),
                'int IDENTITY(-10,1)',
            ],
        ];
    }
}
