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

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);

        // MySQL does not support sequences
        match ($this->db->getDriverName()) {
            'mysql' => $this->assertSame(['id'], $tableSchema->primaryKey),
            default => $this->assertEmpty($tableSchema->primaryKey),
        };

        match ($this->db->getDriverName()) {
            'pgsql' => $this->assertSame('T_sequence_name_id_seq', $tableSchema->sequenceName['id']),
            'oci' => $this->assertStringContainsString('ISEQ$$_', $tableSchema->sequenceName),
            default => $this->assertSame('id', $tableSchema->sequenceName),
        };

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }

    public function testSequenceNameWithMultipleKeys(): void
    {
        $tableName = 'T_sequence_name_multiple_keys';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $this->expectException(Exception::class);

        $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();
    }

    public function testSequenceNameWithPrimaryKey(): void
    {
        $tableName = 'T_sequence_name_with_pk';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);

        match ($this->db->getDriverName()) {
            'pgsql' => $this->assertSame('T_sequence_name_with_pk_id_seq', $tableSchema->sequenceName['id']),
            'oci' => $this->assertStringContainsString('ISEQ$$_', $tableSchema->sequenceName),
            default => $this->assertSame('id', $tableSchema->sequenceName),
        };


        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }

    public function testSequenceNameWithPrimaryKeyComposite(): void
    {
        $tableName = 'T_sequence_name_with_pk_composite';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);

        $this->assertSame(['id1', 'id2'], $tableSchema->primaryKey);

        match ($this->db->getDriverName()) {
            'pgsql' => $this->assertSame(
                'T_sequence_name_with_pk_composite_id1_seq',
                $tableSchema->sequenceName['id1']
            ),
            'oci' => $this->assertStringContainsString('ISEQ$$_', $tableSchema->sequenceName),
            default => $this->assertSame('id1', $tableSchema->sequenceName),
        };

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }
}
