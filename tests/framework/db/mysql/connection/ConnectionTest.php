<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\command;

use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group connection
 */
final class ConnectionTest extends \yiiunit\framework\db\connection\AbstractConnection
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection();
    }
}
