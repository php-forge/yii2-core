<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\provider\types;

use yii\db\mysql\ColumnSchemaBuilder;
use yiiunit\support\TestHelper;

final class BigPrimaryKeyProvider extends \yiiunit\framework\db\provider\types\AbstractBigPrimaryKeyProvider
{
    public static function builder(): array
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

    public function builderWithUnsigned(): array
    {
        return [
            [
                \yii\db\mysql\Schema::TYPE_UBIGPK,
                'ubigpk',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey()->unsigned(),
                'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
            [
                \yii\db\mysql\Schema::TYPE_UBIGPK . '(1)',
                'ubigpk(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->bigPrimaryKey(1)->unsigned(),
                'bigint(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
        ];
    }

    public function raw(): array
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

    public function rawWithUnsigned(): array
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
}
