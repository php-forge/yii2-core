<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\schema;

use yii\db\Connection;
use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group schema
 * @group tableschema
 */
final class TableSchemaTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection();
    }

    public function testAutoIncrement(): void
    {
        $tableName = 'T_autoincrement';
        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY',
            default => 'SERIAL',
        };

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => $columnId,
                'name' => 'VARCHAR(128)',
            ],
        )->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);

        $this->assertEmpty($tableSchema->primaryKey);
        $this->assertSame('T_autoincrement_id_seq', $tableSchema->sequenceName['id']);

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }

    public function testAutoIncrementWithMultipleKeys(): void
    {
        $tableName = 'T_autoincrement_with_multiple_keys';
        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY',
            default => 'SERIAL',
        };

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

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
        $this->assertSame('T_autoincrement_with_multiple_keys_id1_seq', $tableSchema->sequenceName['id1']);
        $this->assertSame('T_autoincrement_with_multiple_keys_id2_seq', $tableSchema->sequenceName['id2']);

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }

    public function testAutoIncrementWithPrimaryKey(): void
    {
        $tableName = 'T_autoincrement_with_pk';
        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY',
            default => 'SERIAL',
        };

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => $columnId . ' NOT NULL PRIMARY KEY',
                'name' => 'VARCHAR(128)',
            ],
        )->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);

        $this->assertSame(['id'], $tableSchema->primaryKey);
        $this->assertSame('T_autoincrement_with_pk_id_seq', $tableSchema->sequenceName['id']);

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }

    public function testAutoIncrementWithPrimaryKeyComposite(): void
    {
        $tableName = 'T_autoincrement_with_pk_composite';
        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY',
            default => 'SERIAL',
        };

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id1' => $columnId . ' NOT NULL',
                'id2' => $columnId . ' NOT NULL',
                'name' => 'VARCHAR(128)',
                'PRIMARY KEY ([[id1]], [[id2]])',
            ],
        )->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);

        $this->assertSame(['id1', 'id2'], $tableSchema->primaryKey);
        $this->assertSame('T_autoincrement_with_pk_composite_id1_seq', $tableSchema->sequenceName['id1']);
        $this->assertSame('T_autoincrement_with_pk_composite_id2_seq', $tableSchema->sequenceName['id2']);

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }
}
