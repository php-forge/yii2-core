<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\command;

use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group command
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\command\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection(true, dirname(__DIR__) . '/fixture/insert.sql');
    }
}
