<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\command;

use yii\db\Connection;
use yii\db\Schema;
use yiiunit\support\MssqlConnection;
use yiiunit\support\TableGenerator;

/**
 * @group db
 * @group mssql
 * @group command
 * @group column-schema-builder
 * @group column-type
 */
final class ColumnTypes extends \yiiunit\TestCase
{
    protected Connection $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    public function testAutoincrement(): void
    {
        $table = 'autoincrement';
        $autoColumn = $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_AUTO);
        $columns = [
            'id' => $autoColumn,
            'name' => $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_STRING)->notNull(),
        ];

        // Ensure column type
        $this->assertSame('auto', $autoColumn->__toString());
        $this->assertSame('int IDENTITY', $this->db->queryBuilder->getColumnType($autoColumn));

        $result = $this->ensureTable($table, $columns);

        // Ensure table was created
        $this->assertSame(0, $result);

        $column = $this->db->getTableSchema($table)->getColumn('id');

        // Ensure column was created
        $this->assertNull($column->isPrimaryKey);
        $this->assertTrue($column->autoIncrement);
        $this->assertSame('integer', $column->type);
        $this->assertFalse($column->allowNull);

        $result = $this->db->createCommand()->batchInsert($table, ['name'], [['test1'], ['test2']])->execute();

        // Ensure data was inserted
        $this->assertSame(2, $result);

        // Ensure last insert ID
        $this->assertSame('2', $this->db->getLastInsertID());

        TableGenerator::ensureNoTable($this->db, $table);
    }

    public function testAutoincrementWithLength(): void
    {
        $table = 'autoincrement';
        $autoColumn = $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_AUTO, [-10, 2]);
        $columns = [
            'id' => $autoColumn,
            'name' => $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_STRING)->notNull(),
        ];

         // Ensure column type
        $this->assertSame('auto(-10,2)', $autoColumn->__toString());
        $this->assertSame('int IDENTITY(-10,2)', $this->db->queryBuilder->getColumnType($autoColumn));

        $result = $this->ensureTable($table, $columns);

        // Ensure table was created
        $this->assertSame(0, $result);

        $column = $this->db->getTableSchema($table)->getColumn('id');

        // Ensure column was created
        $this->assertNull($column->isPrimaryKey);
        $this->assertTrue($column->autoIncrement);
        $this->assertSame('integer', $column->type);
        $this->assertFalse($column->allowNull);

        $result = $this->db->createCommand()->batchInsert($table, ['name'], [['test1'], ['test2']])->execute();

        // Ensure data was inserted
        $this->assertSame(2, $result);

        // Ensure last insert ID
        $this->assertSame('-8', $this->db->getLastInsertID());

        TableGenerator::ensureNoTable($this->db, $table);
    }

    public function testBigAutoincrement(): void
    {
        $table = 'bigautoincrement';
        $autoColumn = $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO);
        $columns = [
            'id' => $autoColumn,
            'name' => $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_STRING)->notNull(),
        ];

        // Ensure column type
        $this->assertSame('bigauto', $autoColumn->__toString());
        $this->assertSame('bigint IDENTITY', $this->db->queryBuilder->getColumnType($autoColumn));

        $result = $this->ensureTable($table, $columns);

        // Ensure table was created
        $this->assertSame(0, $result);

        $column = $this->db->getTableSchema('bigautoincrement')->getColumn('id');

        // Ensure column was created
        $this->assertNull($column->isPrimaryKey);
        $this->assertTrue($column->autoIncrement);
        $this->assertSame('bigint', $column->type);
        $this->assertFalse($column->allowNull);

        $result = $this->db->createCommand()->batchInsert(
            'bigautoincrement',
            ['name'],
            [
                ['test1'],
                ['test2'],
            ],
        )->execute();

        // Ensure data was inserted
        $this->assertSame(2, $result);

        // Ensure last insert ID
        $this->assertSame('2', $this->db->getLastInsertID());

        TableGenerator::ensureNoTable($this->db, 'bigautoincrement');
    }

    public function testBigAutoincrementWithLength(): void
    {
        $table = 'bigautoincrement';
        $autoColumn = $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_BIGAUTO, [-10, 2]);
        $columns = [
            'id' => $autoColumn,
            'name' => $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_STRING)->notNull(),
        ];

        // Ensure column type
        $this->assertSame('bigauto(-10,2)', $autoColumn->__toString());
        $this->assertSame('bigint IDENTITY(-10,2)', $this->db->queryBuilder->getColumnType($autoColumn));

        $result = $this->ensureTable($table, $columns);

        // Ensure table was created
        $this->assertSame(0, $result);

        $column = $this->db->getTableSchema($table)->getColumn('id');

        // Ensure column was created
        $this->assertNull($column->isPrimaryKey);
        $this->assertTrue($column->autoIncrement);
        $this->assertSame('bigint', $column->type);
        $this->assertFalse($column->allowNull);

        $result = $this->db->createCommand()->batchInsert($table, ['name'], [['test1'], ['test2']])->execute();

        // Ensure data was inserted
        $this->assertSame(2, $result);

        // Ensure last insert ID
        $this->assertSame('-8', $this->db->getLastInsertID());

        TableGenerator::ensureNoTable($this->db, $table);
    }

    public function testBigPrimaryKey(): void
    {
        $table = 'bigprimarykey';
        $primaryKeyColumn = $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_BIGPK);
        $columns = [
            'id' => $primaryKeyColumn,
            'name' => $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_STRING)->notNull(),
        ];

        // Ensure column type
        $this->assertSame('bigpk', $primaryKeyColumn->__toString());
        $this->assertSame('bigint IDENTITY PRIMARY KEY', $this->db->queryBuilder->getColumnType($primaryKeyColumn));

        $result = $this->ensureTable($table, $columns);

        // Ensure table was created
        $this->assertSame(0, $result);

        $column = $this->db->getTableSchema($table)->getColumn('id');

        // Ensure column was created
        $this->assertTrue($column->isPrimaryKey);
        $this->assertTrue($column->autoIncrement);
        $this->assertSame('bigint', $column->type);
        $this->assertFalse($column->allowNull);

        $result = $this->db->createCommand()->batchInsert($table, ['name'], [['test1'], ['test2']])->execute();

        // Ensure data was inserted
        $this->assertSame(2, $result);

        // Ensure last insert ID
        $this->assertSame('2', $this->db->getLastInsertID());

        TableGenerator::ensureNoTable($this->db, $table);
    }

    public function testBigPrimaryKeyWithLength(): void
    {
        $table = 'bigautoincrement';
        $primaryKeyColumn = $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_BIGPK, [-10, 2]);
        $columns = [
            'id' => $primaryKeyColumn,
            'name' => $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_STRING)->notNull(),
        ];

        // Ensure column type
        $this->assertSame('bigpk(-10,2)', $primaryKeyColumn->__toString());
        $this->assertSame(
            'bigint IDENTITY(-10,2) PRIMARY KEY',
            $this->db->queryBuilder->getColumnType($primaryKeyColumn),
        );

        $result = $this->ensureTable($table, $columns);

        // Ensure table was created
        $this->assertSame(0, $result);

        $column = $this->db->getTableSchema($table)->getColumn('id');

        // Ensure column was created
        $this->assertTrue($column->isPrimaryKey);
        $this->assertTrue($column->autoIncrement);
        $this->assertSame('bigint', $column->type);
        $this->assertFalse($column->allowNull);

        $result = $this->db->createCommand()->batchInsert($table, ['name'], [['test1'], ['test2']])->execute();

        // Ensure data was inserted
        $this->assertSame(2, $result);

        // Ensure last insert ID
        $this->assertSame('-8', $this->db->getLastInsertID());

        TableGenerator::ensureNoTable($this->db, $table);
    }

    public function testPrimaryKey(): void
    {
        $table = 'primarykey';
        $primaryKeyColumn = $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_PK);
        $columns = [
            'id' => $primaryKeyColumn,
            'name' => $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_STRING)->notNull(),
        ];

        // Ensure column type
        $this->assertSame('pk', $primaryKeyColumn->__toString());
        $this->assertSame('int IDENTITY PRIMARY KEY', $this->db->queryBuilder->getColumnType($primaryKeyColumn));

        $result = $this->ensureTable($table, $columns);

        // Ensure table was created
        $this->assertSame(0, $result);

        $column = $this->db->getTableSchema('primarykey')->getColumn('id');

        // Ensure column was created
        $this->assertTrue($column->isPrimaryKey);
        $this->assertTrue($column->autoIncrement);
        $this->assertSame('integer', $column->type);
        $this->assertFalse($column->allowNull);

        $result = $this->db->createCommand()->batchInsert($table, ['name'], [['test1'], ['test2']])->execute();

        // Ensure data was inserted
        $this->assertSame(2, $result);

        // Ensure last insert ID
        $this->assertSame('2', $this->db->getLastInsertID());

        TableGenerator::ensureNoTable($this->db, 'primarykey');
    }

    public function testPrimaryKeyWithLength(): void
    {
        $table = 'primarykey';
        $primaryKeyColumn = $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_PK, [-10, 2]);
        $columns = [
            'id' => $primaryKeyColumn,
            'name' => $this->db->schema->createColumnSchemaBuilder(Schema::TYPE_STRING)->notNull(),
        ];

        // Ensure column type
        $this->assertSame('pk(-10,2)', $primaryKeyColumn->__toString());
        $this->assertSame('int IDENTITY(-10,2) PRIMARY KEY', $this->db->queryBuilder->getColumnType($primaryKeyColumn));

        $result = $this->ensureTable($table, $columns);

        // Ensure table was created
        $this->assertSame(0, $result);

        $column = $this->db->getTableSchema('primarykey')->getColumn('id');

        // Ensure column was created
        $this->assertTrue($column->isPrimaryKey);
        $this->assertTrue($column->autoIncrement);
        $this->assertSame('integer', $column->type);
        $this->assertFalse($column->allowNull);

        $result = $this->db->createCommand()->batchInsert($table, ['name'], [['test1'], ['test2']])->execute();

        // Ensure data was inserted
        $this->assertSame(2, $result);

        // Ensure last insert ID
        $this->assertSame('-8', $this->db->getLastInsertID());

        TableGenerator::ensureNoTable($this->db, 'primarykey');
    }

    private function ensureTable(string $tableName, array $columns): int
    {
        return TableGenerator::ensureTable($this->db, $tableName, $columns);
    }
}
