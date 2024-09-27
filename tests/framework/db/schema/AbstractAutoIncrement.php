<?php

declare(strict_types=1);

namespace yiiunit\framework\db\schema;

use yii\base\InvalidArgumentException;
use yii\db\Connection;
use yiiunit\support\DbHelper;

abstract class AbstractAutoIncrement extends \yiiunit\TestCase
{
    protected array $columnsSchema = [];
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testGetNextAutoIncrementValue(): void
    {
        $tableName = '{{%T_autoincrement_pk}}';

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);
        $this->assertSame(1, $this->db->getSchema()->getNextAutoIncrementPKValue($tableName, 'id'));

        $result = $this->db->createCommand()->insert($tableName, ['name' => 'test_1'])->execute();

        $this->assertSame(1, $result);

        $result = $this->db->createCommand()->insert($tableName, ['name' => 'test_2'])->execute();

        $this->assertSame(1, $result);
        $this->assertSame(3, $this->db->getSchema()->getNextAutoIncrementPKValue($tableName, 'id'));

        DbHelper::ensureNoTable($this->db, $tableName);
    }

    public function testGetNextAutoIncrementValueWithNoColumnAutoIncrement(): void
    {
        $tableName = '{{%T_autoincrement_pk}}';

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'name' is not an auto-incremental column in table '{$tableName}'.");

        $this->assertSame(1, $this->db->getSchema()->getNextAutoIncrementPKValue($tableName, 'name'));
    }

    public function testGetNextAutoIncrementValueWithNoTableExist(): void
    {
        $tableName = '{{%T_autoincrement_pk}}';

        DbHelper::ensureNoTable($this->db, $tableName);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Table not found: '{$tableName}'.");

        $this->assertSame(1, $this->db->getSchema()->getNextAutoIncrementPKValue($tableName, 'id'));
    }

    public function testResetAutoIncrementPK(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value
    ): void {
        $sequence = '';

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $result = $this->db->getSchema()->resetAutoIncrementPK($tableName, $value);

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

            $sequence = $tableSchema->columns['id']->sequenceName;
        }

        $this->assertEquals(end($expectedIds), $this->db->getLastInsertID($sequence));

        $ids = $this->db->createCommand(
            <<<SQL
            SELECT [[id]] FROM {$tableName}
            SQL
        )->queryColumn();

        $this->assertEquals($expectedIds, $ids);

        DbHelper::ensureNoTable($this->db, $tableName);
    }

    public function testResetAutoIncrementPKWithData(): void
    {
        $tableName = '{{%T_reset_autoincrement_pk}}';

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        foreach (range(1, 3) as $i) {
            $result = $this->db->createCommand()->insert($tableName, ['name' => 'test_' . $i])->execute();

            $this->assertSame(1, $result);
        }

        $result = $this->db->getSchema()->resetAutoIncrementPK($tableName, 7);

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

        DbHelper::ensureNoTable($this->db, $tableName);
    }

    public function testResetAutoIncrementPKWithNotTableExist(): void
    {
        $tableName = '{{%T_reset_autoincrement_pk}}';

        DbHelper::ensureNoTable($this->db, $tableName);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Table not found: '{$tableName}'.");

        $this->db->getSchema()->resetAutoIncrementPK($tableName, 1);
    }

    public function testResetAutoIncrementPKWithTableNotPrimaryKey(): void
    {
        $tableName = '{{%T_reset_autoincrement_pk}}';

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There is no primary key associated with table 'T_reset_autoincrement_pk'.");

        $this->db->getSchema()->resetAutoIncrementPK($tableName, 1);
    }

    public function testResetAutoIncrementPKWithTablePrimaryKeyComposite(): void
    {
        $tableName = '{{%T_reset_autoincrement_pk}}';

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This method does not support tables with composite primary keys.');

        $this->db->getSchema()->resetAutoIncrementPK($tableName, 1);
    }
}
