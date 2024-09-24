<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command;

use yii\db\Connection;

abstract class AbstractCreateSequence extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    protected function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testCreateSequence(
        string $table,
        int $start,
        int $increment,
        array $options,
        array $expectedSequenceInfo
    ): void {
        $this->ensureNoTable($table);

        $result = $this->db->createCommand()->createSequence($table, $start, $increment, $options)->execute();

        $this->assertSame(0, $result);

        $sequenceInfo = $this->db->getSchema()->getSequenceInfo($table);

        $this->assertSame($expectedSequenceInfo, $sequenceInfo);

        $result = $this->db->createCommand()->dropSequence($table)->execute();

        $this->assertSame(0, $result);

        $this->ensureNoTable($table);
    }

    protected function ensureNoTable(string $tableName): void
    {
        if ($this->db->hasTable($tableName)) {
            $this->db->createCommand()->dropTable($tableName)->execute();
            $this->assertFalse($this->db->hasTable($tableName));
        }
    }
}
