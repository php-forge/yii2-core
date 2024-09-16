<?php

declare(strict_types=1);

namespace yiiunit\framework\db\schema;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group schema
 * @group table-schema
 */
final class TableSchemaTest extends \yiiunit\framework\db\schema\AbstractTableSchema
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    public function testSequenceName(): void
    {
        $this->columnsSchema = [
            'id' => 'INT IDENTITY',
            'name' => 'NVARCHAR(128)',
        ];

        parent::testSequenceName();
    }

    public function testSequenceNameWithMultipleKeys(): void
    {
        $this->columnsSchema = [
            'id1' => 'INT IDENTITY',
            'id2' => 'INT IDENTITY',
            'name' => 'NVARCHAR(128)',
        ];

        parent::testSequenceNameWithMultipleKeys();
    }

    public function testSequenceNameWithPrimaryKey(): void
    {
        $this->columnsSchema = [
            'id' => 'INT IDENTITY PRIMARY KEY',
            'name' => 'NVARCHAR(128)',
        ];

        parent::testSequenceNameWithPrimaryKey();
    }

    public function testSequenceNameWithPrimaryKeyComposite(): void
    {
        $this->columnsSchema = [
            'id1' => 'INT IDENTITY',
            'id2' => 'INT',
            'name' => 'NVARCHAR(128)',
            'PRIMARY KEY (id1, id2)',
        ];

        parent::testSequenceNameWithPrimaryKeyComposite();
    }
}
