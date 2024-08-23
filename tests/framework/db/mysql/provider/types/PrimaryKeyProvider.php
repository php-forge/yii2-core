<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\provider\types;

use yii\db\mysql\ColumnSchemaBuilder;
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
