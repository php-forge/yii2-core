<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command\types;

use Closure;
use yii\db\Connection;
use yii\db\Schema;
use yiiunit\support\TableGenerator;

abstract class AbstractExecuteColumnTypes extends \yiiunit\TestCase
{
    protected Connection $db;
    protected string $table = 'column_types';

    public function executeColumnTypes(
        Closure $abstractColumn,
        string $expectedColumnSchemaType,
        bool|null $isPrimaryKey,
        string $expectedColumnType,
        int|string $expectedLastInsertID,
    ): void {
        $columns = [
            'id' => $abstractColumn($this->db->schema),
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

        // Ensure last insert ID
        $this->assertSame($expectedLastInsertID, $this->db->getLastInsertID());

        TableGenerator::ensureNoTable($this->db, $this->table);
    }
}
