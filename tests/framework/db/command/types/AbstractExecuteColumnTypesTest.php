<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command\types;

use Closure;
use yii\db\Connection;
use yii\db\Schema;
use yiiunit\support\TableGenerator;

abstract class AbstractExecuteColumnTypes extends \yiiunit\TestCase
{
    protected Connection|null $db = null;
    protected string $table = 'column_types';

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function executeColumnTypes(
        Closure|string $abstractColumn,
        string $expectedColumnSchemaType,
        bool|null $isPrimaryKey,
        string $expectedColumnType,
        int|string $expectedLastInsertID,
    ): void {
        if (is_callable($abstractColumn)) {
            $abstractColumn = $abstractColumn($this->db->schema);
        }

        $columns = [
            'id' => $abstractColumn,
            'name' => $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_STRING)
        ];

        // Ensure column schema type
        $this->assertSame($expectedColumnSchemaType, $this->db->queryBuilder->getColumnType($columns['id']));

        $result = TableGenerator::ensureTable($this->db, $this->table, $columns);

        // Ensure table was created
        $this->assertSame(0, $result);

        $column = $this->db->getTableSchema($this->table)->getColumn('id');

        // Ensure column was created
        $this->assertSame($isPrimaryKey, $column->isPrimaryKey);
        $this->assertTrue($column->autoIncrement);
        $this->assertSame($expectedColumnType, $column->type);
        $this->assertFalse($column->allowNull);

        $result = $this->db->createCommand()->batchInsert($this->table, ['name'], [['test1'], ['test2']])->execute();

        // Ensure data was inserted
        $this->assertSame(2, $result);

        // Ensure last insert ID.
        // MySQL not return last insert ID for batch insert.
        $lastInsertID = match ($this->db->getDriverName()) {
            'mysql' => $this->db->createCommand("SELECT MAX(id) FROM {$this->table}")->queryScalar(),
            default => $this->db->getLastInsertID(),
        };

        $this->assertSame($expectedLastInsertID, $lastInsertID);

        TableGenerator::ensureNoTable($this->db, $this->table);
    }
}
