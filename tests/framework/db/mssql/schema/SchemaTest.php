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
}
