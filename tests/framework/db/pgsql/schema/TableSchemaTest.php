<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\schema;

use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group schema
 * @group table-schema
 */
final class TableSchemaTest extends \yiiunit\framework\db\schema\AbstractTableSchema
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection();
    }

    public function testSequenceName(): void
    {
        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY',
            default => 'SERIAL',
        };
        $this->columnsSchema = [
            'id' => $columnId,
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceName();
    }

    public function testSequenceNameWithMultipleKeys(): void
    {
        $tableName = 'T_autoincrement_with_multiple_keys';

        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY',
            default => 'SERIAL',
        };

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id1' => $columnId,
                'id2' => $columnId,
                'name' => 'VARCHAR(128)',
            ],
        )->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);
        $this->assertEmpty($tableSchema->primaryKey);
        $this->assertSame(
            'T_autoincrement_with_multiple_keys_id1_seq',
            $this->db->getQuoter()->unquoteSimpleColumnName($tableSchema->sequenceName['id1'])
        );
        $this->assertSame(
            'T_autoincrement_with_multiple_keys_id2_seq',
            $this->db->getQuoter()->unquoteSimpleColumnName($tableSchema->sequenceName['id2'])
        );

        $this->ensureNoTable($tableName);
    }

    public function testSequenceNameWithPrimaryKey(): void
    {
        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY',
            default => 'SERIAL',
        };
        $this->columnsSchema = [
            'id' => $columnId . ' NOT NULL PRIMARY KEY',
            'name' => 'VARCHAR(128)',
        ];

        parent::testSequenceNameWithPrimaryKey();
    }

    public function testSequenceNameWithPrimaryKeyComposite(): void
    {
        $tableName = 'T_sequence_name_with_pk_composite';

        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY',
            default => 'SERIAL',
        };

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id1' => $columnId . ' NOT NULL',
                'id2' => $columnId . ' NOT NULL',
                'name' => 'VARCHAR(128)',
                'PRIMARY KEY ([[id1]], [[id2]])',
            ]
        )->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);
        $this->assertSame(['id1', 'id2'], $tableSchema->primaryKey);
        $this->assertSame('T_sequence_name_with_pk_composite_id1_seq', $tableSchema->sequenceName['id1']);
        $this->assertSame('T_sequence_name_with_pk_composite_id2_seq', $tableSchema->sequenceName['id2']);

        $this->ensureNoTable($tableName);
    }
}