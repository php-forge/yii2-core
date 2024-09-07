<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\command;

use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group connection
 */
final class ConnectionTest extends \yiiunit\framework\db\connection\AbstractConnection
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection();
    }
}
