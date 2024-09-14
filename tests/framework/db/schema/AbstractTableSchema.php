<?php

declare(strict_types=1);

namespace yiiunit\framework\db\schema;

use Exception;
use yii\db\Connection;

abstract class AbstractTableSchema extends \yiiunit\TestCase
{
    protected Connection|null $db = null;
    protected array $columnsSchema = [];

    abstract protected function ensureSequenceName(string $sequenceName): void;

    public function testSequenceName(): void
    {
        $tableName = 'T_sequence_name';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        $this->assertNotNull($tableSchema);
        $this->assertEmpty($tableSchema->primaryKey);
        $this->ensureSequenceName($tableSchema->sequenceName);
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
        $this->ensureSequenceName($tableSchema->sequenceName);
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
        $this->ensureSequenceName($tableSchema->sequenceName);
        $this->ensureNoTable($tableName);
    }

    private function ensureNoTable(string $tableName): void
    {
        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }
    }
}
