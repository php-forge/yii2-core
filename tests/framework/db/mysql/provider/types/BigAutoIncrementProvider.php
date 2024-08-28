<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\provider\types;

use yii\db\mysql\ColumnSchemaBuilder;
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
                'bigint(20) AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                Schema::TYPE_BIGAUTO . '(1)',
                'bigint(1) AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                \yii\db\mysql\Schema::TYPE_UBIGAUTO,
                'bigint(20) UNSIGNED AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                \yii\db\mysql\Schema::TYPE_UBIGAUTO . '(1)',
                'bigint(1) UNSIGNED AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            // builder
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO),
                'bigint(20) AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO, 1),
                'bigint(1) AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(\yii\db\mysql\Schema::TYPE_UBIGAUTO),
                'bigint(20) UNSIGNED AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                'id' => static fn (Schema $schema) => $schema
                    ->createColumnSchemaBuilder(\yii\db\mysql\Schema::TYPE_UBIGAUTO, 1),
                'bigint(1) UNSIGNED AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            // builder shortcut
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigAutoIncrement(),
                'bigint(20) AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigAutoIncrement(1),
                'bigint(1) AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                'id' => static fn (Schema $schema) => $schema
                    ->createColumnSchemaBuilder()
                    ->bigAutoIncrement()
                    ->unsigned(),
                'bigint(20) UNSIGNED AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                'id' => static fn (Schema $schema) => $schema
                    ->createColumnSchemaBuilder()
                    ->bigAutoIncrement(1)
                    ->unsigned(),
                'bigint(1) UNSIGNED AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
        ];
    }

    public static function queryBuilder(): array
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

        $types = parent::queryBuilder();

        return TestHelper::addExpected($expected, $types);
    }
}
