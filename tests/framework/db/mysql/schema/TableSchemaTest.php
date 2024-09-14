<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\schema;

use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group schema
 * @group table-schema
 */
final class TableSchemaTest extends \yiiunit\framework\db\schema\AbstractTableSchema
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }

    public function testSequenceName(): void
    {
        $this->columnsSchema = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceName();
    }

    public function testSequenceNameWithMultipleKeys(): void
    {
        $this->columnsSchema = [
            'id1' => 'INT AUTO_INCREMENT',
            'id2' => 'INT AUTO_INCREMENT',
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceNameWithMultipleKeys();
    }

    public function testSequenceNameWithPrimaryKey(): void
    {
        $this->columnsSchema = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceNameWithPrimaryKey();
    }

    public function testSequenceNameWithPrimaryKeyComposite(): void
    {
        $this->columnsSchema = [
            'id1' => 'INT AUTO_INCREMENT',
            'id2' => 'INT',
            'name' => 'VARCHAR(128)',
            'PRIMARY KEY (id1, id2)',
        ];

        parent::testSequenceNameWithPrimaryKeyComposite();
    }
}
