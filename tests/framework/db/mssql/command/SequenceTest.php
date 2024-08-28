<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\command;

use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group command
 * @group sequence
 */
final class SequenceTest extends \yiiunit\framework\db\command\AbstractSequence
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection(true);
    }
}
