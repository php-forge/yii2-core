<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\provider\types;

use yii\db\mysql\ColumnSchemaBuilder;
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

    public function builderWithUnsigned(): array
    {
        return [
            [
                \yii\db\mysql\Schema::TYPE_UPK,
                'upk',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey()->unsigned(),
                'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
            [
                \yii\db\mysql\Schema::TYPE_UPK . '(1)',
                'upk(1)',
                static fn (ColumnSchemaBuilder $builder) => $builder->primaryKey(1)->unsigned(),
                'int(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ],
        ];
    }

    public static function schema(): array
    {
        return [
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK),
                'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK, 1),
                'int(1) NOT NULL AUTO_INCREMENT PRIMARY KEY',
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
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK)->unsigned(),
                'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(Schema::TYPE_PK, 1)->unsigned(),
                'int(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(\yii\db\mysql\Schema::TYPE_UPK),
                'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder(\yii\db\mysql\Schema::TYPE_UPK, 1),
                'int(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
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

    public function rawWithUnsigned(): array
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
