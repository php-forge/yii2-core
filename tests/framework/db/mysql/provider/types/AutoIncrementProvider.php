<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\provider\types;

use yii\db\mysql\ColumnSchemaBuilder;
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

    public function builderWithUnsigned(): array
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

    public static function schema(): array
    {
        return [
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO),
                'int(11) AUTO_INCREMENT',
                true,
                'integer',
                2,
            ],
            [
                'id' => static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO, 1),
                'int(1) AUTO_INCREMENT',
                true,
                'integer',
                2,
            ],
        ];
    }

    public static function schemaWithUnsigned(): array
    {
        return [
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_AUTO)->unsigned(),
                'int(10) UNSIGNED AUTO_INCREMENT',
                true,
                'integer',
                2,
            ],
            [
                'id' => static fn (Schema $schema) => $schema
                    ->createColumnSchemaBuilder(Schema::TYPE_AUTO, 1)
                    ->unsigned(),
                'int(1) UNSIGNED AUTO_INCREMENT',
                true,
                'integer',
                2,
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(\yii\db\mysql\Schema::TYPE_UAUTO),
                'int(10) UNSIGNED AUTO_INCREMENT',
                true,
                'integer',
                2,
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(\yii\db\mysql\Schema::TYPE_UAUTO, 1),
                'int(1) UNSIGNED AUTO_INCREMENT',
                true,
                'integer',
                2,
            ],
        ];
    }

    public function raw(): array
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

    public function rawWithUnsigned(): array
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
}
