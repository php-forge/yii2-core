<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\schema;

use yiiunit\support\OciConnection;

/**
 * @group db
 * @group oci
 * @group schema
 * @group table-schema
 */
final class TableSchemaTest extends \yiiunit\framework\db\schema\AbstractTableSchema
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = OciConnection::getConnection();
    }

    public function testSequenceName(): void
    {
        $this->columnsSchema = [
            'id' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL',
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceName();
    }

    public function testSequenceNameWithMultipleKeys(): void
    {
        $this->columnsSchema = [
            'id1' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL',
            'id2' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL',
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceNameWithMultipleKeys();
    }

    public function testSequenceNameWithPrimaryKey(): void
    {
        $this->columnsSchema = [
            'id' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY',
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceNameWithPrimaryKey();
    }

    public function testSequenceNameWithPrimaryKeyComposite(): void
    {
        $this->columnsSchema = [
            'id1' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL',
            'id2' => 'NUMBER(10) NOT NULL',
            'name' => 'VARCHAR(128)',
            'PRIMARY KEY ([[id1]], [[id2]])',
        ];

        parent::testSequenceNameWithPrimaryKeyComposite();
    }
}
