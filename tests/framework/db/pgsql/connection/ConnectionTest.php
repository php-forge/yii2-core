<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\command;

use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group connection
 */
final class ConnectionTest extends \yiiunit\framework\db\connection\AbstractConnection
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = PgsqlConnection::getConnection();
    }
}
