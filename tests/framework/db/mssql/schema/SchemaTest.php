<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\schema;

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

    /**
     * @dataProvider \yiiunit\framework\db\mssql\provider\SchemaProvider::resetSequence
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
