<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\schema;

use yii\base\InvalidArgumentException;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group schema
 * @group auto-increment
 */
final class AutoIncrementTest extends \yiiunit\framework\db\schema\AbstractAutoIncrement
{
    protected array $columnsSchema = [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'name' => 'TEXT',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection();
    }

    /**
     * @dataProvider \yiiunit\framework\db\sqlite\provider\SchemaProvider::resetAutoIncrementPK
     */
    public function testResetAutoIncrementPK(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value = null
    ): void {
        parent::testResetAutoIncrementPK($tableName, $insertRows, $expectedIds, $value);
    }

    public function testResetAutoIncrementPKWithTableNotPrimaryKey(): void
    {
        $this->columnsSchema = [
            'id' => 'INTEGER',
            'name' => 'TEXT',
        ];

        parent::testResetAutoIncrementPKWithTableNotPrimaryKey();
    }

    public function testResetAutoIncrementPKWithTablePrimaryKeyComposite(): void
    {
        $this->columnsSchema = [
            'id' => 'INTEGER',
            'user_id' => 'INTEGER',
            'name' => 'TEXT',
            'PRIMARY KEY (id, user_id)',
        ];

        parent::testResetAutoIncrementPKWithTablePrimaryKeyComposite();
    }

    public function testResetAutoIncrementPKWithValueNegative(): void
    {
        $tableName = '{{%T_reset_auto_increment_pk}}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The value must be greater than '0'.");

        $this->db->getSchema()->resetAutoIncrementPK($tableName, -1);
    }
}
