<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\command;

use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group command
 * @group insert
 */
final class InsertTest extends \yiiunit\framework\db\command\AbstractInsert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = SqliteConnection::getConnection(true);
    }
}
