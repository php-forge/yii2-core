<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\provider\types;

use yii\db\mssql\ColumnSchemaBuilder;
use yii\db\Schema;
use yiiunit\support\TestHelper;

final class AutoIncrementProvider extends \yiiunit\framework\db\provider\types\AbstractAutoIncrementProvider
{
    public static function command(): array
    {
        return [
            // default
            [
                Schema::TYPE_AUTO,
                'int IDENTITY',
                null,
                'integer',
                '2',
            ],
            [
                Schema::TYPE_AUTO . '(1)',
                'int IDENTITY',
                null,
                'integer',
                '2',
            ],
            [
                Schema::TYPE_AUTO . '(0,0)',
                'int IDENTITY(0,1)',
                null,
                'integer',
                '1',
            ],
            [
                Schema::TYPE_AUTO . '(1,1)',
                'int IDENTITY(1,1)',
                null,
                'integer',
                '2',
            ],
            [
                Schema::TYPE_AUTO . '(2,3)',
                'int IDENTITY(2,3)',
                null,
                'integer',
                '5',
            ],
            [
                Schema::TYPE_AUTO . '(-10,1)',
                'int IDENTITY(-10,1)',
                null,
                'integer',
                '-9',
            ],
            // builder
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO),
                'int IDENTITY',
                null,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO, [1]),
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
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO, [1, 1]),
                'int IDENTITY(1,1)',
                null,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO, [2, 3]),
                'int IDENTITY(2,3)',
                null,
                'integer',
                '5',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO, [-10, 1]),
                'int IDENTITY(-10,1)',
                null,
                'integer',
                '-9',
            ],
            // builder shortcut
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->autoIncrement(),
                'int IDENTITY',
                null,
                'integer',
                '2',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->autoIncrement(1),
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
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->autoIncrement(1, 1),
                'int IDENTITY(1,1)',
                null,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->autoIncrement(2, 3),
                'int IDENTITY(2,3)',
                null,
                'integer',
                '5',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->autoIncrement(-10, 1),
                'int IDENTITY(-10,1)',
                null,
                'integer',
                '-9',
            ],
        ];
    }

    public static function queryBuilder(): array
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

        $types = parent::queryBuilder();

        return TestHelper::addExpected($expected, $types);
    }
}
