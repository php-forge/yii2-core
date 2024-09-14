<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\schema;

use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group schema
 */
final class SchemaTest extends \yiiunit\framework\db\schema\AbstractSchema
{
    protected array $columnsSchema = [
        'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
        'name' => 'VARCHAR(128)',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }
}
