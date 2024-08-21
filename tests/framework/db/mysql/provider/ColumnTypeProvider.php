<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\provider;

use yii\db\mysql\ColumnSchemaBuilder;
use yii\db\Schema;
use yiiunit\support\TestHelper;

final class ColumnTypeProvider extends \yiiunit\framework\db\provider\AbstractColumnTypeProvider
{
    public static function autoIncrement(): array
    {
        $expected = [
            'auto' => [
                1 => 'auto',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(),
                3 => 'int(11) AUTO_INCREMENT',
            ],
            'auto(1)' => [
                1 => 'auto(1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(1),
                3 => 'int(1) AUTO_INCREMENT',
            ],
        ];

        $types = parent::autoIncrement();

        return TestHelper::addExpected($expected, $types);
    }

    public function autoIncrementWithRaw(): array
    {
        return [
            [
                'int(11) AUTO_INCREMENT',
                'auto',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(),
            ],
            [
                'int(1) AUTO_INCREMENT',
                'auto(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(1),
                'int(1) AUTO_INCREMENT',
            ],
        ];
    }

    public static function bigAutoIncrement(): array
    {
        $expected = [
            'bigauto' => [
                1 => 'bigauto',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(),
                3 => 'bigint(20) AUTO_INCREMENT',
            ],
            'bigauto(1)' => [
                1 => 'bigauto(1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(1),
                3 => 'bigint(1) AUTO_INCREMENT',
            ],
        ];

        $types = parent::bigAutoIncrement();

        return TestHelper::addExpected($expected, $types);
    }

    public function bigAutoIncrementWithRaw(): array
    {
        return [
            [
                'bigint(20) AUTO_INCREMENT',
                'bigauto',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(),
            ],
            [
                'bigint(1) AUTO_INCREMENT',
                'bigauto(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(1),
                'bigint(1) AUTO_INCREMENT',
            ],
        ];
    }

    public static function bigPrimaryKey(): array
    {
        $expected = [
            'bigpk' => [
                1 => 'bigpk',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(),
                3 => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
            'bigpk(1)' => [
                1 => 'bigpk(1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(1),
                3 => 'bigint(1) NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
        ];

        $types = parent::bigPrimaryKey();

        return TestHelper::addExpected($expected, $types);
    }

    public function bigPrimaryKeyWithRaw(): array
    {
        return [
            [
                'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'bigpk',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(),
            ],
            [
                'bigint(1) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'bigpk(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(1),
                'bigint(1) NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
        ];
    }

    public static function primaryKey(): array
    {
        $expected = [
            'pk' => [
                1 => 'pk',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(),
                3 => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
            'pk(1)' => [
                1 => 'pk(1)',
                2 => static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(1),
                3 => 'int(1) NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
        ];

        $types = parent::primaryKey();

        return TestHelper::addExpected($expected, $types);
    }

    public function primaryKeyWithRaw(): array
    {
        return [
            [
                'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'pk',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(),
            ],
            [
                'int(1) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'pk(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(1),
                'int(1) NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
        ];
    }

    public function unsignedAutoIncrement(): array
    {
        return [
            [
                \yii\db\mysql\Schema::TYPE_UAUTO,
                'uauto',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement()->unsigned(),
                'int(10) UNSIGNED AUTO_INCREMENT',
            ],
            [
                \yii\db\mysql\Schema::TYPE_UAUTO . '(1)',
                'uauto(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(1)->unsigned(),
                'int(1) UNSIGNED AUTO_INCREMENT',
            ],
        ];
    }

    public function unsignedAutoIncrementWithRaw(): array
    {
        return [
            [
                'int(10) UNSIGNED AUTO_INCREMENT',
                'uauto',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement()->unsigned(),
            ],
            [
                'int(1) UNSIGNED AUTO_INCREMENT',
                'uauto(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->autoIncrement(1)->unsigned(),
                'int(1) UNSIGNED AUTO_INCREMENT',
            ],
        ];
    }

    public function unsignedBigAutoIncrement(): array
    {
        return [
            [
                \yii\db\mysql\Schema::TYPE_UBIGAUTO,
                'ubigauto',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement()->unsigned(),
                'bigint(20) UNSIGNED AUTO_INCREMENT',
            ],
            [
                \yii\db\mysql\Schema::TYPE_UBIGAUTO . '(1)',
                'ubigauto(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(1)->unsigned(),
                'bigint(1) UNSIGNED AUTO_INCREMENT',
            ],
        ];
    }

    public function unsignedBigAutoIncrementWithRaw(): array
    {
        return [
            [
                'bigint(20) UNSIGNED AUTO_INCREMENT',
                'ubigauto',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement()->unsigned(),
            ],
            [
                'bigint(1) UNSIGNED AUTO_INCREMENT',
                'ubigauto(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigAutoIncrement(1)->unsigned(),
                'bigint(1) UNSIGNED AUTO_INCREMENT',
            ],
        ];
    }

    public function unsignedBigPrimaryKey(): array
    {
        return [
            [
                Schema::TYPE_UBIGPK,
                'ubigpk',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey()->unsigned(),
                'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
            [
                Schema::TYPE_UBIGPK . '(1)',
                'ubigpk(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(1)->unsigned(),
                'bigint(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
        ];
    }

    public function unsignedBigPrimaryKeyWithRaw(): array
    {
        return [
            [
                'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'ubigpk',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey()->unsigned(),
            ],
            [
                'bigint(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'ubigpk(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(1)->unsigned(),
                'bigint(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
        ];
    }

    public function unsignedPrimaryKey(): array
    {
        return [
            [
                Schema::TYPE_UPK,
                'upk',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey()->unsigned(),
                'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
            [
                Schema::TYPE_UPK . '(1)',
                'upk(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(1)->unsigned(),
                'int(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
        ];
    }

    public function unsignedPrimaryKeyWithRaw(): array
    {
        return [
            [
                'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'upk',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey()->unsigned(),
            ],
            [
                'int(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'upk(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(1)->unsigned(),
                'int(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
        ];
    }
}
