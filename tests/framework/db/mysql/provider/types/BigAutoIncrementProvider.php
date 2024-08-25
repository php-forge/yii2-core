<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\provider\types;

use yii\db\mysql\ColumnSchemaBuilder;
use yii\db\Schema;
use yiiunit\support\TestHelper;

final class BigAutoIncrementProvider extends \yiiunit\framework\db\provider\types\AbstractBigAutoIncrementProvider
{
    public static function builder(): array
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

    public function builderWithUnsigned(): array
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

    public static function schema(): array
    {
        return [
            // schema
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
            // schema with unsigned
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO)->unsigned(),
                'bigint(20) UNSIGNED AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                'id' => static fn (Schema $schema) => $schema
                    ->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO, 1)
                    ->unsigned(),
                'bigint(1) UNSIGNED AUTO_INCREMENT',
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
            // builder generator
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigAutoIncrement(),
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
            // builder generator with unsigned
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->bigAutoIncrement()->unsigned(),
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
            // raw sql
            [
                'bigint(20) AUTO_INCREMENT',
                'bigint(20) AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                'bigint(1) AUTO_INCREMENT',
                'bigint(1) AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            // raw sql with unsigned
            [
                'bigint(20) UNSIGNED AUTO_INCREMENT',
                'bigint(20) UNSIGNED AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
            [
                'bigint(1) UNSIGNED AUTO_INCREMENT',
                'bigint(1) UNSIGNED AUTO_INCREMENT',
                true,
                'bigint',
                2,
            ],
        ];
    }

    public function raw(): array
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

    public function rawWithUnsigned(): array
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
}
