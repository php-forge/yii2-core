<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\schema;

use yii\base\InvalidArgumentException;
use yiiunit\support\{DbHelper, MssqlConnection};

/**
 * @group db
 * @group mssql
 * @group schema
 * @group auto-increment
 */
final class AutoIncrementTest extends \yiiunit\framework\db\schema\AbstractAutoIncrement
{
    protected array $columnsSchema = [
        'id' => 'INT IDENTITY PRIMARY KEY',
        'name' => 'NVARCHAR(128)',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\SchemaProvider::resetAutoIncrementPK
     */
    public function testResetAutoIncrementPK(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value = null
    ): void {
        parent::testResetAutoIncrementPK($tableName, $insertRows, $expectedIds, $value);
    }

    public function testResetAutoIncrementPKWithTableNotAutoIncrement(): void
    {
        $tableName = '{{%T_reset_auto_increment_pk}}';

        $this->columnsSchema = [
            'id' => 'INT',
            'name' => 'NVARCHAR(128)',
            'PRIMARY KEY (id)',
        ];

        DbHelper::ensureNoTable($this->db, $tableName);

        $result = $this->db->createCommand()->createTable($tableName, $this->columnsSchema)->execute();

        $this->assertSame(0, $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The column 'id' is not an auto-incremental column.");

        $this->db->getSchema()->resetAutoIncrementPK($tableName, 1);
    }

    public function testResetAutoIncrementPKWithTableNotPrimaryKey(): void
    {
        $this->columnsSchema = [
            'id' => 'INT',
            'name' => 'NVARCHAR(128)',
        ];

        parent::testResetAutoIncrementPKWithTableNotPrimaryKey();
    }

    public function testResetAutoIncrementPKWithTablePrimaryKeyComposite(): void
    {
        $this->columnsSchema = [
            'id' => 'INT IDENTITY',
            'user_id' => 'INT',
            'name' => 'NVARCHAR(128)',
            'PRIMARY KEY (id, user_id)',
        ];

        parent::testResetAutoIncrementPKWithTablePrimaryKeyComposite();
    }
}
