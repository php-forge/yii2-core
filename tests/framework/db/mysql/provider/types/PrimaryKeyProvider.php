<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\provider\types;

use yii\db\mysql\ColumnSchemaBuilder;
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
                'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            [
                Schema::TYPE_PK . '(1)',
                'int(1) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            [
                \yii\db\mysql\Schema::TYPE_UPK,
                'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            [
                \yii\db\mysql\Schema::TYPE_UPK . '(1)',
                'int(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            // builder
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
            // builder shorcut
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->primaryKey(),
                'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->primaryKey(1),
                'int(1) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->primaryKey()->unsigned(),
                'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
            [
                static fn (Schema $schema) => $schema->createColumnSchemaBuilder()->primaryKey(1)->unsigned(),
                'int(1) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                true,
                'integer',
                2,
            ],
        ];
    }

    public static function queryBuilder(): array
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

        $types = parent::queryBuilder();

        return TestHelper::addExpected($expected, $types);
    }
}
