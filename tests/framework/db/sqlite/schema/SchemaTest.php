<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\schema;

use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group schema
 */
final class SchemaTest extends \yiiunit\framework\db\schema\AbstractSchema
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
}