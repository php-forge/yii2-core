<?php

declare(strict_types=1);

namespace yiiunit\framework\db\schema;

use yii\base\InvalidArgumentException;
use yii\db\Connection;
use yiiunit\TestCase;

abstract class AbstractSchema extends TestCase
{
    protected Connection|null $db = null;
    protected array $columnsSchema = [];

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testGetNextAutoIncrementValue(): void
    {
        $tableName = '{{%reset_sequence}}';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);
        $this->assertSame(1, $this->db->getSchema()->getNextAutoIncrementValue($tableName, 'id'));

        $result = $this->db->createCommand()->insert($tableName, ['name' => 'test_1'])->execute();

        $this->assertSame(1, $result);

        $result = $this->db->createCommand()->insert($tableName, ['name' => 'test_2'])->execute();

        $this->assertSame(1, $result);
        $this->assertSame(3, $this->db->getSchema()->getNextAutoIncrementValue($tableName, 'id'));

        $this->ensureNoTable($tableName);
    }

    public function testGetNextAutoIncrementValueWithNoColumnAutoIncrement(): void
    {
        $tableName = '{{%reset_sequence}}';

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'name' is not an auto-incremental column in table '{$tableName}'.");

        $this->assertSame(1, $this->db->getSchema()->getNextAutoIncrementValue($tableName, 'name'));
    }

    public function testGetNextAutoIncrementValueWithNoTableExist(): void
    {
        $tableName = '{{%reset_sequence}}';

        $this->ensureNoTable($tableName);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Table not found: '{$tableName}'.");

        $this->assertSame(1, $this->db->getSchema()->getNextAutoIncrementValue($tableName, 'id'));
    }

    public function testResetSequence(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value
    ): void {
        $sequenceName = '';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->getSchema()->resetSequence($tableName, $value);

        if (in_array($this->db->driverName, ['mysql', 'pgsql', 'sqlite']) && $value === 0) {
            $this->assertSame(1, $result);
        } else {
            $this->assertSame($value ?? 1, $result);
        }

        foreach ($insertRows as $inserRow) {
            $result = $this->db->createCommand()->insert($tableName, $inserRow)->execute();

            $this->assertSame(1, $result);
        }

        if ($this->db->driverName === 'oci') {
            $tableSchema = $this->db->getTableSchema($tableName);
            $sequenceName = $tableSchema->sequenceName;
        }

        $this->assertEquals(end($expectedIds), $this->db->getLastInsertID($sequenceName));

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT [[id]] FROM {$tableName}
            SQL
        )->queryColumn();

        $this->assertEquals($expectedIds, $ids);

        $this->ensureNoTable($tableName);
    }

    public function testResetSequenceWithData(): void
    {
        $tableName = '{{%reset_sequence}}';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        foreach (range(1, 3) as $i) {
            $result = $this->db->createCommand()->insert($tableName, ['name' => 'test_' . $i])->execute();

            $this->assertSame(1, $result);
        }

        $result = $this->db->getSchema()->resetSequence($tableName, 7);

        $this->assertSame(7, $result);

        foreach (range(8, 10) as $i) {
            $result = $this->db->createCommand()->insert($tableName, ['name' => 'test_' . $i])->execute();

            $this->assertSame(1, $result);
        }

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT [[id]] FROM {$tableName}
            SQL
        )->queryColumn();

        match ($this->db->driverName) {
            'sqlsrv' => $this->assertEquals([1, 2, 3, 8, 9, 10], $ids),
            default => $this->assertEquals([1, 2, 3, 7, 8, 9], $ids),
        };

        $this->ensureNoTable($tableName);
    }

    public function testResetSequenceWithNotTableExist(): void
    {
        $tableName = '{{%reset_sequence}}';

        $this->ensureNoTable($tableName);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Table not found: '{$tableName}'.");

        $this->db->getSchema()->resetSequence($tableName, 1);
    }

    public function testResetSequenceWithTableNotPrimaryKey(): void
    {
        $tableName = '{{%reset_sequence}}';

        $this->ensureNoTable($tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There is no primary key or sequence associated with table 'reset_sequence'.");

        $this->db->getSchema()->resetSequence($tableName, 1);
    }

    protected function ensureNoTable(string $tableName): void
    {
        if ($this->db->hasTable($tableName)) {
            $this->db->createCommand()->dropTable($tableName)->execute();
            $this->assertFalse($this->db->hasTable($tableName));
        }
    }
}
