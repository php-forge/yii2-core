<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\command;

use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group command
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\command\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MysqlConnection::getConnection(true);
    }
}
