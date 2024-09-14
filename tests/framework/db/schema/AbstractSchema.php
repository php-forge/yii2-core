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
        $this->ensureNoTable('T_autoincrement');

        $result = $this->db->createCommand()->createTable('T_autoincrement', $this->columnsSchema)->execute();

        $this->assertSame(0, $result);
        $this->assertSame(1, $this->db->getSchema()->getNextAutoIncrementValue('T_autoincrement', 'id'));

        $result = $this->db->createCommand()->insert('T_autoincrement', ['name' => 'test_1'])->execute();

        $this->assertSame(1, $result);

        $result = $this->db->createCommand()->insert('T_autoincrement', ['name' => 'test_2'])->execute();

        $this->assertSame(1, $result);
        $this->assertSame(3, $this->db->getSchema()->getNextAutoIncrementValue('T_autoincrement', 'id'));
        $this->ensureNoTable('T_autoincrement');
    }

    public function testGetNextAutoIncrementValueWithNoColumnAutoIncrement(): void
    {
        $this->ensureNoTable('T_autoincrement');

        $result = $this->db->createCommand()->createTable('T_autoincrement', $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'name' is not an auto-incremental column in table 'T_autoincrement'.");

        $this->assertSame(1, $this->db->getSchema()->getNextAutoIncrementValue('T_autoincrement', 'name'));
    }

    public function testGetNextAutoIncrementValueWithNoTableExist(): void
    {
        $this->ensureNoTable('T_autoincrement');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Table not found: 'T_autoincrement'.");

        $this->assertSame(1, $this->db->getSchema()->getNextAutoIncrementValue('T_autoincrement', 'id'));
    }

    private function ensureNoTable(string $tableName): void
    {
        if ($this->db->hasTable($tableName)) {
            $this->db->createCommand()->dropTable($tableName)->execute();
            $this->assertFalse($this->db->hasTable($tableName));
        }
    }
}
