<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\schema;

use Exception;
use yii\db\Connection;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group schema
 * @group tableschema
 */
final class TableSchemaTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection();
    }

    public function testAutoIncrement(): void
    {
        $tableName = 'T_autoincrement';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'INT PRIMARY KEY',
                'name' => 'VARCHAR(128)',
            ],
        )->execute();

        $this->assertSame(0, $result);

        $table = $this->db->getTableSchema($tableName);

        $this->assertNotNull($table);

        $this->assertSame(['id'], $table->primaryKey);
        $this->assertSame(['id'], $table->sequenceName);

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }

    public function testAutoIncrementWithMultipleKeys(): void
    {
        $tableName = 'T_autoincrement_with_multiple_keys';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $this->expectException(Exception::class);

        $this->db->createCommand()->createTable(
            $tableName,
            [
                'id1' => 'INT PRIMARY KEY',
                'id2' => 'INT PRIMARY KEY',
                'name' => 'VARCHAR(128)',
            ],
        )->execute();
    }

    public function testAutoIncrementWithPrimaryKey(): void
    {
        $tableName = 'T_autoincrement_with_pk';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id' => 'INT PRIMARY KEY',
                'name' => 'VARCHAR(128)',
            ],
        )->execute();

        $this->assertSame(0, $result);

        $table = $this->db->getTableSchema($tableName);

        $this->assertNotNull($table);

        $this->assertSame(['id'], $table->primaryKey);
        $this->assertSame(['id'], $table->sequenceName);

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }

    public function testAutoIncrementWithPrimaryKeyComposite(): void
    {
        $tableName = 'T_autoincrement_with_pk_composite';

        if ($this->db->hasTable($tableName)) {
            $result = $this->db->createCommand()->dropTable($tableName)->execute();

            $this->assertSame(0, $result);
        }

        $result = $this->db->createCommand()->createTable(
            $tableName,
            [
                'id1' => 'INT',
                'id2' => 'INT',
                'name' => 'VARCHAR(128)',
                'PRIMARY KEY (id1, id2)',
            ],
        )->execute();

        $this->assertSame(0, $result);

        $table = $this->db->getTableSchema($tableName);

        $this->assertNotNull($table);

        $this->assertSame(['id1', 'id2'], $table->primaryKey);

        // SQLite does not support sequences for composite primary keys
        $this->assertEmpty($table->sequenceName);

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }
}
