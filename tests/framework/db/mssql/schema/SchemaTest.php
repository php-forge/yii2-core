<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\schema;

use yii\base\InvalidArgumentException;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group schema
 */
final class SchemaTest extends \yiiunit\framework\db\schema\AbstractSchema
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

    public function testGetSequenceInfoWithNotExist(): void
    {
        $this->assertFalse($this->db->getSchema()->getSequenceInfo('{{%not_exists}}'));
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
        $tableName = '{{%reset_autoincrement_pk}}';

        $this->columnsSchema = [
            'id' => 'INT',
            'name' => 'NVARCHAR(128)',
            'PRIMARY KEY (id)',
        ];

        $this->ensureNoTable($tableName);

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
