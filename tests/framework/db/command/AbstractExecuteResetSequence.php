<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command;

use yii\base\InvalidArgumentException;
use yii\db\Connection;
use yiiunit\TestCase;

use function end;

abstract class AbstractExecuteResetSequence extends TestCase
{
    protected Connection|null $db = null;
    protected array $columnSchema = [];

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testExecuteResetSequence(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value
    ): void {
        $sequenceName = '';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable($tableName, $this->columnSchema)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->createCommand()->executeResetSequence($tableName, $value);

        $this->assertSame(0, $result);

        $tableSchema = $this->db->getTableSchema($tableName);

        foreach ($insertRows as $inserRow) {
            $result = $this->db->createCommand()->insert($tableName, $inserRow)->execute();

            $this->assertSame(1, $result);
        }

        if ($this->db->getDriverName() === 'oci') {
            $sequenceName = $tableSchema->sequenceName;
        }

        $this->assertEquals(end($expectedIds), $this->db->getLastInsertID($sequenceName));

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT [[id]] FROM {$tableName}
            SQL
        )->queryColumn();

        $this->assertEquals($expectedIds, $ids);

        $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertFalse($this->db->hasTable($tableName));
    }

    public function testExecuteResetSequenceWithTableNoExist(): void
    {
        $tableName = 'non_existent_table';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Table not found: 'non_existent_table'.");

        $this->db->createCommand()->executeResetSequence($tableName);
    }

    public function testExecuteResetSequenceWithTableNotSequence(): void
    {
        $tableName = 'no_sequence_table';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable($tableName, $this->columnSchema)->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There is no primary key or sequence associated with table 'no_sequence_table'.");

        $this->db->createCommand()->executeResetSequence($tableName);
    }

    public function testExecuteResetSequenceWithTablePrimaryKeyComposite(): void
    {
        $tableName = 'composite_primary_key_table';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable($tableName, $this->columnSchema)->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);

        if ($this->db->driverName === 'sqlite') {
            $this->expectExceptionMessage(
                "There is no primary key or sequence associated with table 'composite_primary_key_table'."
            );
        } else {
            $this->expectExceptionMessage('This method does not support tables with composite primary keys.');
        }

        $this->db->createCommand()->executeResetSequence($tableName);
    }
}
