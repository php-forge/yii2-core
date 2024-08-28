<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\provider\types;

use yii\db\mssql\ColumnSchemaBuilder;
use yii\db\Schema;
use yiiunit\support\TestHelper;

final class PrimaryKeyProvider extends \yiiunit\framework\db\provider\types\AbstractPrimaryKeyProvider
{
    public static function command(): array
    {
        return [
            // default
            [
                Schema::TYPE_PK,
                'int IDENTITY PRIMARY KEY',
                true,
                'integer',
                '2',
            ],
            [
                Schema::TYPE_PK . '(1)',
                'int IDENTITY PRIMARY KEY',
                true,
                'integer',
                '2',
            ],
            [
                Schema::TYPE_PK . '(0,0)',
                'int IDENTITY(0,1) PRIMARY KEY',
                true,
                'integer',
                '1',
            ],
            [
                Schema::TYPE_PK . '(1,1)',
                'int IDENTITY(1,1) PRIMARY KEY',
                true,
                'integer',
                '2',
            ],
            [
                Schema::TYPE_PK . '(2,3)',
                'int IDENTITY(2,3) PRIMARY KEY',
                true,
                'integer',
                '5',
            ],
            [
                Schema::TYPE_PK . '(-10,1)',
                'int IDENTITY(-10,1) PRIMARY KEY',
                true,
                'integer',
                '-9',
            ],
            // builder
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK),
                'int IDENTITY PRIMARY KEY',
                true,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK, [1]),
                'int IDENTITY PRIMARY KEY',
                true,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK, [0, 0]),
                'int IDENTITY(0,1) PRIMARY KEY',
                true,
                'integer',
                '1',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK, [1, 1]),
                'int IDENTITY(1,1) PRIMARY KEY',
                true,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK, [2, 3]),
                'int IDENTITY(2,3) PRIMARY KEY',
                true,
                'integer',
                '5',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK, [-10, 1]),
                'int IDENTITY(-10,1) PRIMARY KEY',
                true,
                'integer',
                '-9',
            ],
            // builder shortcut
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->primaryKey(),
                'int IDENTITY PRIMARY KEY',
                true,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->primaryKey(1),
                'int IDENTITY PRIMARY KEY',
                true,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->primaryKey(0, 0),
                'int IDENTITY(0,1) PRIMARY KEY',
                true,
                'integer',
                '1',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->primaryKey(1, 1),
                'int IDENTITY(1,1) PRIMARY KEY',
                true,
                'integer',
                '2',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->primaryKey(2, 3),
                'int IDENTITY(2,3) PRIMARY KEY',
                true,
                'integer',
                '5',
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->primaryKey(-10, 1),
                'int IDENTITY(-10,1) PRIMARY KEY',
                true,
                'integer',
                '-9',
            ],
        ];
    }

    public static function queryBuilder(): array
    {
        $expected = [
            'pk' => [
                1 => 'pk',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(),
                3 => 'int IDENTITY PRIMARY KEY',
            ],
            'pk(1)' => [
                1 => 'pk',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(1),
                3 => 'int IDENTITY PRIMARY KEY',
            ],
            'pk(0,0)' => [
                1 => 'pk(0,1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(0, 0),
                3 => 'int IDENTITY(0,1) PRIMARY KEY',
            ],
            'pk(1,1)' => [
                1 => 'pk(1,1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(1, 1),
                3 => 'int IDENTITY(1,1) PRIMARY KEY',
            ],
            'pk(2,3)' => [
                1 => 'pk(2,3)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(2, 3),
                3 => 'int IDENTITY(2,3) PRIMARY KEY',
            ],
            'pk(-10,1)' => [
                Schema::TYPE_PK . '(-10,1)',
                'pk(-10,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(-10, 1),
                'int IDENTITY(-10,1) PRIMARY KEY',
            ],
        ];

        $types = parent::queryBuilder();

        return TestHelper::addExpected($expected, $types);
    }
}
