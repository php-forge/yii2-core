<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\schema;

use Exception;
use yii\db\Connection;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group schema
 * @group tableschema
 */
final class TableSchemaTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
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
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'name' => 'VARCHAR(128)',
            ],
        )->execute();

        $this->assertSame(0, $result);

        $table = $this->db->getTableSchema($tableName);

        $this->assertNotNull($table);

        $this->assertSame(['id'], $table->primaryKey);
        $this->assertSame('id', $table->sequenceName);

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
                'id1' => 'INT AUTO_INCREMENT',
                'id2' => 'INT AUTO_INCREMENT',
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
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'name' => 'VARCHAR(128)',
            ],
        )->execute();

        $this->assertSame(0, $result);

        $table = $this->db->getTableSchema($tableName);

        $this->assertNotNull($table);

        $this->assertSame(['id'], $table->primaryKey);
        $this->assertSame('id', $table->sequenceName);

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
                'id1' => 'INT AUTO_INCREMENT',
                'id2' => 'INT',
                'name' => 'VARCHAR(128)',
                'PRIMARY KEY (id1, id2)',
            ],
        )->execute();

        $this->assertSame(0, $result);

        $table = $this->db->getTableSchema($tableName);

        $this->assertNotNull($table);

        $this->assertSame(['id1', 'id2'], $table->primaryKey);
        $this->assertSame('id1', $table->sequenceName);

        $result = $this->db->createCommand()->dropTable($tableName)->execute();

        $this->assertSame(0, $result);
    }
}
