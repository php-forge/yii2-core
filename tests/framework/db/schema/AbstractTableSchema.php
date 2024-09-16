<?php

declare(strict_types=1);

namespace yiiunit\framework\db\schema;

use Exception;
use yii\db\Connection;

abstract class AbstractTableSchema extends \yiiunit\TestCase
{
    protected Connection|null $db = null;
    protected array $columnsSchema = [];

    public function testSequenceName(): void
    {
        $tableName = 'T_sequence_name';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);

        // In MySQL, SQLite, columns with auto-increment (`AUTOINCREMENT`) must be primary keys
        match ($this->db->driverName) {
            'mysql', 'sqlite' => $this->assertSame(['id'], $tableSchema->primaryKey),
            default => $this->assertEmpty($tableSchema->primaryKey),
        };

        // MySQL, SQLite does not support sequences
        match ($this->db->driverName) {
            'oci' => $this->assertStringContainsString('ISEQ$$_', $tableSchema->sequenceName),
            'pgsql' => $this->assertSame('T_sequence_name_id_seq', $tableSchema->sequenceName['id']),
            default => $this->assertSame('id', $tableSchema->sequenceName),
        };

        $this->ensureNoTable($tableName);
    }

    public function testSequenceNameWithMultipleKeys(): void
    {
        $tableName = 'T_sequence_name_multiple_keys';

        $this->ensureNoTable($tableName);
        $this->expectException(Exception::class);

        $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();
    }

    public function testSequenceNameWithPrimaryKey(): void
    {
        $tableName = 'T_sequence_name_with_pk';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);
        $this->assertSame(['id'], $tableSchema->primaryKey);

        // MySQL, SQLite does not support sequences
        match ($this->db->getDriverName()) {
            'oci' => $this->assertStringContainsString('ISEQ$$_', $tableSchema->sequenceName),
            'pgsql' => $this->assertSame('T_sequence_name_with_pk_id_seq', $tableSchema->sequenceName['id']),
            default => $this->assertSame('id', $tableSchema->sequenceName),
        };

        $this->ensureNoTable($tableName);
    }

    public function testSequenceNameWithPrimaryKeyComposite(): void
    {
        $tableName = 'T_sequence_name_with_pk_composite';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);
        $this->assertSame(['id1', 'id2'], $tableSchema->primaryKey);

        // MySQL, SQLite does not support sequences
        match ($this->db->driverName) {
            'oci' => $this->assertStringContainsString('ISEQ$$_', $tableSchema->sequenceName),
            'sqlite' => $this->assertEmpty($tableSchema->sequenceName),
            default => $this->assertSame('id1', $tableSchema->sequenceName),
        };

        $this->ensureNoTable($tableName);
    }

    protected function ensureNoTable(string $tableName): void
    {
        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }
    }
}
