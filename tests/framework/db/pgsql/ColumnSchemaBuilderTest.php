<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql;

use yii\db\ColumnSchemaBuilder;

/**
 * ColumnSchemaBuilderTest tests ColumnSchemaBuilder for PostgreSQL.
 *
 * @group db
 * @group pgsql
 * @group column-schema-builder
 */
class ColumnSchemaBuilderTest extends \yiiunit\framework\db\ColumnSchemaBuilderTest
{
    public $driverName = 'pgsql';

    public function getColumnSchemaBuilder($type, $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($this->getConnection(), $type, $length);
    }
}
