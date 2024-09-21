<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\schema;

use yii\base\InvalidArgumentException;
use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group schema
 */
final class SchemaTest extends \yiiunit\framework\db\schema\AbstractSchema
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection();

        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY',
            default => 'SERIAL PRIMARY KEY',
        };

        $this->columnsSchema = [
            'id' => $columnId,
            'name' => 'VARCHAR(128)',
        ];
    }

    /**
     * @dataProvider \yiiunit\framework\db\pgsql\provider\SchemaProvider::resetAutoIncrementPK
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
            'id' => 'INT',
            'name' => 'VARCHAR(128)',
        ];

        parent::testResetAutoIncrementPKWithTableNotPrimaryKey();
    }

    public function testResetAutoIncrementPKWithTablePrimaryKeyComposite(): void
    {
        $columnId = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => 'INT GENERATED ALWAYS AS IDENTITY',
            default => 'SERIAL',
        };

        $this->columnsSchema = [
            'id' => $columnId,
            'user_id' => 'INT',
            'name' => 'VARCHAR(128)',
            'PRIMARY KEY (id, user_id)',
        ];

        parent::testResetAutoIncrementPKWithTablePrimaryKeyComposite();
    }

    public function testResetAutoIncrementPKWithValueNegative(): void
    {
        $tableName = '{{%reset_autoincrement_pk}}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The value must be greater than '0'.");

        $this->db->getSchema()->resetAutoIncrementPK($tableName, -1);
    }
}
