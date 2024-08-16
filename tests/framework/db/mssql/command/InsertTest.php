<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\command;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group command
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\command\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection(true);
    }
}
