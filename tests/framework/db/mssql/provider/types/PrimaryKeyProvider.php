<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\provider\types;

use yii\db\mssql\ColumnSchemaBuilder;
use yii\db\Schema;
use yiiunit\support\TestHelper;

final class PrimaryKeyProvider extends \yiiunit\framework\db\provider\types\AbstractPrimaryKeyProvider
{
    public static function builder(): array
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

        $types = parent::primaryKey();

        return TestHelper::addExpected($expected, $types);
    }

    public static function schema(): array
    {
        return [
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK),
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
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK, [-10, 2]),
                'int IDENTITY(-10,2) PRIMARY KEY',
                true,
                'integer',
                '-8',
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK, [2, 3]),
                'int IDENTITY(2,3) PRIMARY KEY',
                true,
                'integer',
                '5',
            ],
        ];
    }

    public static function raw(): array
    {
        return [
            [
                'int IDENTITY PRIMARY KEY',
                'pk',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(),
            ],
            [
                'int IDENTITY(1) PRIMARY KEY',
                'pk',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(1),
                'int IDENTITY PRIMARY KEY',
            ],
            [
                'int IDENTITY(0,0) PRIMARY KEY',
                'pk(0,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(0, 0),
                'int IDENTITY(0,1) PRIMARY KEY',
            ],
            [
                'int IDENTITY(1,1) PRIMARY KEY',
                'pk(1,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(1, 1),
            ],
            [
                'int IDENTITY(2,3) PRIMARY KEY',
                'pk(2,3)',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(2, 3),
            ],
            [
                'int IDENTITY(-10,1) PRIMARY KEY',
                'pk(-10,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(-10, 1),
                'int IDENTITY(-10,1) PRIMARY KEY',
            ],
        ];
    }
}
