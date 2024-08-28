<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci;

use yii\db\oci\ColumnSchemaBuilder;
use yii\db\Schema;

/**
 * ColumnSchemaBuilderTest tests ColumnSchemaBuilder for Oracle.
 *
 * @group db
 * @group oci
 * @group column-schema-builder
 */
class ColumnSchemaBuilderTest extends \yiiunit\framework\db\ColumnSchemaBuilderTest
{
    public $driverName = 'oci';

    public function getColumnSchemaBuilder($type, $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($this->getConnection(), $type, $length);
    }
}
