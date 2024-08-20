<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql;

use yii\db\mysql\ColumnSchemaBuilder;
use yii\db\mysql\Schema;

/**
 * ColumnSchemaBuilderTest tests ColumnSchemaBuilder for MySQL.
 *
 * @group db
 * @group mysql
 * @group column-schema-builder
 */
class ColumnSchemaBuilderTest extends \yiiunit\framework\db\ColumnSchemaBuilderTest
{
    protected $driverName = 'mysql';

    public function getColumnSchemaBuilder($type, $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($this->getConnection(), $type, $length);
    }

    /**
     * @return array
     */
    public function typesProvider()
    {
        return [
            ['integer UNSIGNED', Schema::TYPE_INTEGER, null, [
                ['unsigned'],
            ]],
            ['integer(10) UNSIGNED', Schema::TYPE_INTEGER, 10, [
                ['unsigned'],
            ]],
            ['integer(10) COMMENT \'test\'', Schema::TYPE_INTEGER, 10, [
                ['comment', 'test'],
            ]],
            // https://github.com/yiisoft/yii2/issues/11945 # TODO: real test against database
            ['string(50) NOT NULL COMMENT \'Property name\' COLLATE ascii_general_ci', Schema::TYPE_STRING, 50, [
                ['comment', 'Property name'],
                ['append', 'COLLATE ascii_general_ci'],
                ['notNull']
            ]],
        ];
    }
}
