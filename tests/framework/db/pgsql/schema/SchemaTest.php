<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\schema;

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

        $this->columnsSchema = match (version_compare($this->db->serverVersion, '10.0', '>=')) {
            true => [
                'id' => 'INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY',
                'name' => 'VARCHAR(128)',
            ],
            default => [
                'id' => 'SERIAL PRIMARY KEY',
                'name' => 'VARCHAR(128)',
            ],
        };
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
}
