<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\schema;

use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group schema
 * @group table-schema
 */
final class TableSchemaTest extends \yiiunit\framework\db\schema\AbstractTableSchema
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection();
    }

    public function testSequenceName(): void
    {
        $this->columnsSchema = [
            'id' => 'INT PRIMARY KEY',
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceName();
    }

    public function testSequenceNameWithMultipleKeys(): void
    {
        $this->columnsSchema = [
            'id1' => 'INT PRIMARY KEY',
            'id2' => 'INT PRIMARY KEY',
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceNameWithMultipleKeys();
    }

    public function testSequenceNameWithPrimaryKey(): void
    {
        $this->columnsSchema = [
            'id' => 'INT PRIMARY KEY',
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceNameWithPrimaryKey();
    }

    public function testSequenceNameWithPrimaryKeyComposite(): void
    {
        $this->columnsSchema = [
            'id1' => 'INT',
            'id2' => 'INT',
            'name' => 'VARCHAR(128)',
            'PRIMARY KEY (id1, id2)',
        ];

        parent::testSequenceNameWithPrimaryKeyComposite();
    }
}
