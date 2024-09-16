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
     * @dataProvider \yiiunit\framework\db\pgsql\provider\SchemaProvider::resetSequence
     */
    public function testResetSequence(
        string $tableName,
        array $insertRows,
        array $expectedIds,
        int|null $value = null
    ): void {
        parent::testResetSequence($tableName, $insertRows, $expectedIds, $value);
    }

    public function testResetSequenceWithTableNotPrimaryKey(): void
    {
        $this->columnsSchema = [
            'id' => 'INT',
            'name' => 'VARCHAR(128)',
        ];

        parent::testResetSequenceWithTableNotPrimaryKey();
    }

    public function testResetSequenceWithTablePrimaryKeyComposite(): void
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

        parent::testResetSequenceWithTablePrimaryKeyComposite();
    }

    public function testResetSequenceWithValueNegative(): void
    {
        $tableName = '{{%reset_sequence}}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The value must be greater than '0'.");

        $this->db->getSchema()->resetSequence($tableName, -1);
    }
}
