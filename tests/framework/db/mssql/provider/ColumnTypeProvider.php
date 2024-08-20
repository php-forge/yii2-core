<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\provider;

use yii\db\mssql\ColumnSchemaBuilder;
use yiiunit\support\TestHelper;

final class ColumnTypeProvider extends \yiiunit\framework\db\provider\AbstractColumnTypeProvider
{
    public static function autoIncrement(): array
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
        ];

        $types = parent::autoIncrement();

        return TestHelper::addExpected($expected, $types);
    }

    public static function autoIncrementWithRaw(): array
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
        ];
    }

    public static function bigAutoIncrement(): array
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
        ];

        $types = parent::bigAutoIncrement();

        return TestHelper::addExpected($expected, $types);
    }

    public static function bigAutoIncrementWithRaw(): array
    {
        return [
            [
                'bigint IDENTITY',
                'bigauto',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(),
            ],
            [
                'bigint IDENTITY(1)',
                'bigauto',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(1),
                'bigint IDENTITY',
            ],
            [
                'bigint IDENTITY(0,0)',
                'bigauto(0,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(0, 0),
                'bigint IDENTITY(0,1)',
            ],
            [
                'bigint IDENTITY(1,1)',
                'bigauto(1,1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(1, 1),
            ],
            [
                'bigint IDENTITY(2,3)',
                'bigauto(2,3)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(2, 3),
            ],
        ];
    }

    public static function bigPrimaryKey(): array
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
        ];

        $types = parent::bigPrimaryKey();

        return TestHelper::addExpected($expected, $types);
    }

    public static function bigPrimaryKeyWithRaw(): array
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
        ];
    }

    public static function primaryKey(): array
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
        ];

        $types = parent::primaryKey();

        return TestHelper::addExpected($expected, $types);
    }

    public static function primaryKeyWithRaw(): array
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
        ];
    }
}
